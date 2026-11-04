<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Donations\StartOnlineDonation;
use App\Support\CardFee;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

/**
 * The public donate form, laid out like the one on spinneyhill.org.uk. Submitting it opens Stripe Checkout; the
 * gift is recorded only once Stripe confirms the payment.
 */
final class PublicDonationForm extends Component
{
    public const array PRESET_AMOUNTS = ['5', '10', '25', '50'];

    public const array TITLES = ['Mr', 'Mrs', 'Miss', 'Ms', 'Mx', 'Dr'];

    private const int ATTEMPTS_PER_TEN_MINUTES = 10;

    public string $frequency = 'one-off';

    /** Empty once the donor types their own amount. Not null: Livewire would restore the default on the next request. */
    public string $preset = '10';

    public string $other_amount = '';

    public bool $cover_fee = false;

    public string $title = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public bool $gift_aid = false;

    public string $house = '';

    public string $street = '';

    public string $town = '';

    public string $postcode = '';

    public bool $contact_optin = false;

    public bool $consent = false;

    /** Left empty by people; bots that fill every field are ignored. */
    public string $website = '';

    public function updatedPreset(): void
    {
        $this->other_amount = '';
        $this->resetErrorBag('other_amount');
    }

    public function updatedOtherAmount(): void
    {
        if (filled($this->other_amount)) {
            $this->preset = '';
        }
    }

    public function giftPence(): int
    {
        if (filled($this->other_amount)) {
            return is_numeric($this->other_amount) ? max(0, (int) round((float) $this->other_amount * 100)) : 0;
        }

        return in_array($this->preset, self::PRESET_AMOUNTS, true) ? (int) $this->preset * 100 : 0;
    }

    public function feePence(): int
    {
        return CardFee::toCover($this->giftPence());
    }

    public function submit(StartOnlineDonation $startDonation): void
    {
        if (filled($this->website)) {
            return;
        }

        $this->validate();

        $rateLimitKey = 'donate:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::ATTEMPTS_PER_TEN_MINUTES)) {
            $this->addError('form', 'Too many attempts. Please wait a few minutes and try again.');

            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        try {
            $checkoutUrl = $startDonation->handle([
                'frequency' => $this->frequency,
                'gift_pence' => $this->giftPence(),
                'cover_fee' => $this->cover_fee,
                'title' => $this->title ?: null,
                'first_name' => mb_trim($this->first_name),
                'last_name' => mb_trim($this->last_name),
                'email' => mb_trim($this->email),
                'phone' => mb_trim($this->phone) ?: null,
                'gift_aid' => $this->gift_aid,
                'house' => mb_trim($this->house) ?: null,
                'street' => mb_trim($this->street) ?: null,
                'town' => mb_trim($this->town) ?: null,
                'postcode' => mb_strtoupper(mb_trim($this->postcode)) ?: null,
                'contact_optin' => $this->contact_optin,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('form', 'We could not open the secure payment page. Please try again, or call us on 0300 124 0945.');

            return;
        }

        $this->redirect($checkoutUrl);
    }

    public function render(): View
    {
        $giftPence = $this->giftPence();
        $feePence = $this->feePence();

        return view('livewire.public-donation-form', [
            'gift' => CardFee::format($giftPence),
            'fee' => CardFee::format($feePence),
            'total' => CardFee::format($giftPence + ($this->cover_fee ? $feePence : 0)),
            'giftWithGiftAid' => CardFee::format((int) round($giftPence * 1.25)),
            'hasAmount' => $giftPence > 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'frequency' => ['required', Rule::in(['one-off', 'monthly'])],
            'preset' => ['nullable', Rule::in(self::PRESET_AMOUNTS)],
            'other_amount' => [Rule::requiredIf(blank($this->preset)), 'nullable', 'numeric', 'decimal:0,2', 'min:1', 'max:10000'],
            'cover_fee' => ['boolean'],
            'title' => ['nullable', Rule::in(self::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gift_aid' => ['boolean'],
            'house' => [Rule::requiredIf($this->gift_aid), 'nullable', 'string', 'max:100'],
            'street' => [Rule::requiredIf($this->gift_aid), 'nullable', 'string', 'max:150'],
            'town' => [Rule::requiredIf($this->gift_aid), 'nullable', 'string', 'max:100'],
            'postcode' => [Rule::requiredIf($this->gift_aid), 'nullable', 'string', 'max:10'],
            'contact_optin' => ['boolean'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'other_amount.required' => 'Please choose an amount or enter your own.',
            'other_amount.numeric' => 'Please enter an amount in pounds, for example 15 or 15.50.',
            'other_amount.decimal' => 'Please enter an amount in pounds, for example 15 or 15.50.',
            'other_amount.min' => 'The smallest gift we can take online is £1.',
            'other_amount.max' => 'For gifts over £10,000 please contact us or give by bank transfer.',
            'house.required' => 'We need your house name or number to claim Gift Aid.',
            'street.required' => 'We need your street to claim Gift Aid.',
            'town.required' => 'We need your town or city to claim Gift Aid.',
            'postcode.required' => 'We need your postcode to claim Gift Aid.',
            'consent.accepted' => 'Please confirm you have read the privacy policy.',
        ];
    }
}
