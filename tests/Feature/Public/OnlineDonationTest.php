<?php

declare(strict_types=1);

use App\Actions\Donations\StartOnlineDonation;
use App\Enums\DonationPaymentMethod;
use App\Enums\GiftAidDeclarationMethod;
use App\Enums\GiftAidStatus;
use App\Livewire\PublicDonationForm;
use App\Models\Donation;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use App\Models\Team;
use App\Support\CardFee;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;

use function Pest\Livewire\livewire;

const CHECKOUT_URL = 'https://checkout.stripe.com/c/pay/cs_test_new';
const WEBHOOK_SECRET = 'whsec_test_secret';

beforeEach(function (): void {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();

    config([
        'services.stripe.secret' => 'sk_test_secret',
        'services.stripe.webhook_secret' => WEBHOOK_SECRET,
        'services.stripe.team_id' => $this->team->id,
        'services.stripe.fee_percent' => 1.2,
        'services.stripe.fee_fixed_pence' => 20,
    ]);

    Http::preventStrayRequests();
});

/**
 * Metadata as StartOnlineDonation sends it to Stripe.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function donatePageMetadata(array $overrides = []): array
{
    return [
        'source' => StartOnlineDonation::SOURCE,
        'frequency' => 'one-off',
        'gift_pence' => '1000',
        'fee_pence' => '33',
        'title' => 'Ms',
        'first_name' => 'Amina',
        'last_name' => 'Patel',
        'email' => 'amina@example.com',
        'phone' => '07700900123',
        'gift_aid' => '1',
        'house' => '12',
        'street' => 'Green Lane',
        'town' => 'Leicester',
        'postcode' => 'LE5 4HJ',
        'contact_optin' => '1',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function paidCheckoutSession(array $overrides = []): array
{
    return [
        'id' => 'cs_test_paid',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'payment_status' => 'paid',
        'amount_total' => 1033,
        'created' => now()->getTimestamp(),
        'metadata' => donatePageMetadata(),
        ...$overrides,
    ];
}

/**
 * Posts an event to the webhook, signed the way Stripe signs it.
 *
 * @param  array<string, mixed>  $object
 */
function postStripeEvent(string $type, array $object, ?string $secret = WEBHOOK_SECRET): TestResponse
{
    $payload = json_encode(['id' => 'evt_test', 'type' => $type, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
    $timestamp = now()->getTimestamp();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

    return test()->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], $payload);
}

function fillDonorDetails(Testable $form): Testable
{
    return $form
        ->set('first_name', 'Amina')
        ->set('last_name', 'Patel')
        ->set('email', 'amina@example.com')
        ->set('consent', true);
}

describe('donate page', function (): void {
    it('shows the donate form with the bank transfer details', function (): void {
        $this->get(route('donate'))
            ->assertOk()
            ->assertSeeLivewire(PublicDonationForm::class)
            ->assertSee('Help us reach further')
            ->assertSee('53589677')
            ->assertDontSee('Your payment was not completed');
    });

    it('tells a donor who left Stripe that they were not charged', function (): void {
        $this->get(route('donate', ['cancelled' => 1]))
            ->assertOk()
            ->assertSee('Your payment was not completed and you have not been charged.');
    });

    it('works out the card fee the same way as the website', function (int $giftPence, int $feePence): void {
        expect(CardFee::toCover($giftPence))->toBe($feePence);
    })->with([
        '£5' => [500, 27],
        '£10' => [1000, 33],
        '£25' => [2500, 51],
        '£50' => [5000, 81],
    ]);

    it('shows the running total and the Gift Aid boost as the donor chooses', function (): void {
        livewire(PublicDonationForm::class)
            ->assertSee('You are giving')
            ->set('preset', '25')
            ->set('cover_fee', true)
            ->assertSee(['£25.51', 'as a one-off gift', '(including £0.51 to cover card fees)'])
            ->set('frequency', 'monthly')
            ->assertSee('every month')
            ->set('other_amount', '40')
            ->set('first_name', 'Amina')
            ->assertSet('preset', '')
            ->assertSee(['You are giving', '£40', 'every month']);
    });
});

describe('starting a donation', function (): void {
    it('sends a one-off gift to Stripe Checkout without saving anything yet', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_new', 'url' => CHECKOUT_URL])]);

        fillDonorDetails(livewire(PublicDonationForm::class))
            ->set('cover_fee', true)
            ->set('gift_aid', true)
            ->set('house', '12')
            ->set('street', 'Green Lane')
            ->set('town', 'Leicester')
            ->set('postcode', 'le5 4hj')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(CHECKOUT_URL);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk_test_secret')
            && $request['mode'] === 'payment'
            && $request['submit_type'] === 'donate'
            && $request['customer_email'] === 'amina@example.com'
            && $request['line_items'][0]['price_data']['unit_amount'] === 1033
            && ! isset($request['line_items'][0]['price_data']['recurring'])
            && $request['metadata']['gift_pence'] === '1000'
            && $request['metadata']['fee_pence'] === '33'
            && $request['metadata']['postcode'] === 'LE5 4HJ'
            && $request['success_url'] === route('donate.thank-you').'?session_id={CHECKOUT_SESSION_ID}');

        expect(Donation::query()->count())->toBe(0)
            ->and(People::query()->where('email', 'amina@example.com')->exists())->toBeFalse();
    });

    it('sets up a monthly gift as a Stripe subscription that carries the donor details', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_new', 'url' => CHECKOUT_URL])]);

        fillDonorDetails(livewire(PublicDonationForm::class))
            ->set('frequency', 'monthly')
            ->set('other_amount', '15.50')
            ->call('submit')
            ->assertRedirect(CHECKOUT_URL);

        Http::assertSent(fn (Request $request): bool => $request['mode'] === 'subscription'
            && $request['line_items'][0]['price_data']['unit_amount'] === 1550
            && $request['line_items'][0]['price_data']['recurring']['interval'] === 'month'
            && $request['subscription_data']['metadata']['source'] === StartOnlineDonation::SOURCE
            && $request['subscription_data']['metadata']['frequency'] === 'monthly');
    });

    it('requires the donor details and the privacy policy confirmation', function (): void {
        livewire(PublicDonationForm::class)
            ->call('submit')
            ->assertHasErrors(['first_name' => 'required', 'last_name' => 'required', 'email' => 'required', 'consent' => 'accepted'])
            ->assertSee('Please confirm you have read the privacy policy.');
    });

    it('requires an amount of at least £1', function (string $otherAmount, string $message): void {
        fillDonorDetails(livewire(PublicDonationForm::class))
            ->set('other_amount', '5')
            ->set('other_amount', $otherAmount)
            ->call('submit')
            ->assertHasErrors('other_amount')
            ->assertSee($message);
    })->with([
        'no amount' => ['', 'Please choose an amount or enter your own.'],
        'under £1' => ['0.50', 'The smallest gift we can take online is £1.'],
    ]);

    it('asks for the home address when the donor chooses Gift Aid', function (): void {
        fillDonorDetails(livewire(PublicDonationForm::class))
            ->set('gift_aid', true)
            ->call('submit')
            ->assertHasErrors(['house' => 'required', 'street' => 'required', 'town' => 'required', 'postcode' => 'required'])
            ->assertSee('We need your postcode to claim Gift Aid.');
    });

    it('tells the donor when Stripe cannot be reached', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['error' => ['message' => 'Down']], 500)]);

        fillDonorDetails(livewire(PublicDonationForm::class))
            ->call('submit')
            ->assertHasErrors('form')
            ->assertNoRedirect()
            ->assertSee('We could not open the secure payment page.');
    });

    it('ignores submissions that fill the hidden spam trap', function (): void {
        Http::fake();

        fillDonorDetails(livewire(PublicDonationForm::class))
            ->set('website', 'https://spam.example')
            ->call('submit')
            ->assertNoRedirect();

        Http::assertNothingSent();
    });

    it('stops repeated attempts from one address', function (): void {
        Http::fake();

        foreach (range(1, 10) as $attempt) {
            RateLimiter::hit('donate:127.0.0.1', 600);
        }

        fillDonorDetails(livewire(PublicDonationForm::class))
            ->call('submit')
            ->assertHasErrors('form')
            ->assertSee('Too many attempts.');

        Http::assertNothingSent();
    });
});

describe('recording a paid gift', function (): void {
    it('records the donor, their Gift Aid declaration and the gift when they return from Stripe', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_paid' => Http::response(paidCheckoutSession())]);

        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))
            ->assertOk()
            ->assertSee('Thank you, Amina!');

        $person = People::query()->where('email', 'amina@example.com')->sole();
        $declaration = GiftAidDeclaration::query()->where('people_id', $person->id)->sole();
        $donation = Donation::query()->sole();

        expect($person->team_id)->toBe($this->team->id)
            ->and($person->name)->toBe('Amina Patel')
            ->and($person->address)->toBe('12 Green Lane, Leicester')
            ->and($person->consent_communications)->toBeTrue()
            ->and($declaration->method)->toBe(GiftAidDeclarationMethod::Online)
            ->and($declaration->postcode)->toBe('LE5 4HJ')
            ->and($donation->donor->is($person))->toBeTrue()
            ->and($donation->amount)->toBe('10.33')
            ->and($donation->payment_method)->toBe(DonationPaymentMethod::Online)
            ->and($donation->reference)->toBe('cs_test_paid')
            ->and($donation->gift_aid_status)->toBe(GiftAidStatus::Eligible)
            ->and($donation->gift_aid_declaration_id)->toBe($declaration->id)
            ->and($donation->notes)->toContain('£0.33')->toContain('Title: Ms.');
    });

    it('adds the gift to an existing person without overwriting what staff recorded', function (): void {
        $person = People::factory()->for($this->team)->create([
            'first_name' => 'Amina',
            'last_name' => 'Patel-Khan',
            'email' => 'Amina@Example.com',
            'phone' => '0116 000 0000',
            'address' => null,
        ]);
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_paid' => Http::response(paidCheckoutSession())]);

        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))->assertOk();

        $person->refresh();

        expect(People::query()->whereRaw('LOWER(email) = ?', ['amina@example.com'])->count())->toBe(1)
            ->and(Donation::query()->sole()->donor->is($person))->toBeTrue()
            ->and($person->last_name)->toBe('Patel-Khan')
            ->and($person->phone)->toBe('0116 000 0000')
            ->and($person->address)->toBe('12 Green Lane, Leicester');
    });

    it('records each payment once when the donor returns and the webhook also arrives', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_paid' => Http::response(paidCheckoutSession())]);

        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))->assertOk();
        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))->assertOk();
        postStripeEvent('checkout.session.completed', paidCheckoutSession())->assertOk();

        expect(Donation::query()->count())->toBe(1)
            ->and(GiftAidDeclaration::query()->count())->toBe(1);
    });

    it('records nothing for a session that is not paid', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_paid' => Http::response(paidCheckoutSession(['payment_status' => 'unpaid']))]);

        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))->assertOk();

        expect(Donation::query()->count())->toBe(0);
    });

    it('records nothing for a session from the website\'s own donate form', function (): void {
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_paid' => Http::response(paidCheckoutSession(['metadata' => ['source' => 'wordpress']]))]);

        $this->get(route('donate.thank-you', ['session_id' => 'cs_test_paid']))->assertRedirect(route('donate'));
        postStripeEvent('checkout.session.completed', paidCheckoutSession(['metadata' => ['source' => 'wordpress']]))->assertOk();

        expect(Donation::query()->count())->toBe(0);
    });

    it('does not record a gift without Gift Aid as Gift Aid', function (): void {
        postStripeEvent('checkout.session.completed', paidCheckoutSession([
            'metadata' => donatePageMetadata(['gift_aid' => '0', 'house' => '', 'street' => '', 'town' => '', 'postcode' => '']),
        ]))->assertOk();

        expect(Donation::query()->sole()->gift_aid_status)->toBe(GiftAidStatus::NotEligible)
            ->and(GiftAidDeclaration::query()->count())->toBe(0);
    });
});

describe('stripe webhook', function (): void {
    it('rejects events that are not signed with the webhook secret', function (): void {
        postStripeEvent('checkout.session.completed', paidCheckoutSession(), secret: 'whsec_wrong')
            ->assertStatus(400);

        expect(Donation::query()->count())->toBe(0);
    });

    it('records the later months of a monthly gift under the declaration made with the first', function (): void {
        $monthlyMetadata = donatePageMetadata(['frequency' => 'monthly', 'fee_pence' => '0']);
        postStripeEvent('checkout.session.completed', paidCheckoutSession(['mode' => 'subscription', 'amount_total' => 1000, 'metadata' => $monthlyMetadata]))->assertOk();

        $this->travel(1)->month();

        postStripeEvent('invoice.paid', [
            'id' => 'in_test_month_two',
            'object' => 'invoice',
            'billing_reason' => 'subscription_cycle',
            'status' => 'paid',
            'amount_paid' => 1000,
            'created' => now()->getTimestamp(),
            'parent' => ['subscription_details' => ['metadata' => $monthlyMetadata]],
        ])->assertOk();

        $renewal = Donation::query()->where('reference', 'in_test_month_two')->sole();

        expect(Donation::query()->count())->toBe(2)
            ->and(GiftAidDeclaration::query()->count())->toBe(1)
            ->and($renewal->amount)->toBe('10.00')
            ->and($renewal->donated_at->isSameDay(now()))->toBeTrue()
            ->and($renewal->gift_aid_status)->toBe(GiftAidStatus::Eligible)
            ->and($renewal->notes)->toContain('Monthly gift');
    });

    it('leaves the first month to the checkout session so it is not recorded twice', function (): void {
        postStripeEvent('invoice.paid', [
            'id' => 'in_test_month_one',
            'object' => 'invoice',
            'billing_reason' => 'subscription_create',
            'status' => 'paid',
            'amount_paid' => 1000,
            'created' => now()->getTimestamp(),
            'subscription_details' => ['metadata' => donatePageMetadata(['frequency' => 'monthly'])],
        ])->assertOk();

        expect(Donation::query()->count())->toBe(0);
    });
});
