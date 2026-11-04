<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\CreationSource;
use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidDeclarationMethod;
use App\Models\Donation;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use App\Models\Team;
use App\Support\CardFee;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Saves a paid gift from the public donate page: finds or adds the donor by email, records a Gift Aid declaration
 * when they gave one, then the donation. The Stripe id is the donation's reference, so the thank-you page and the
 * webhook can both report the same payment without it being recorded twice.
 */
final readonly class RecordOnlineDonation
{
    private const array PAID_STATUSES = ['paid', 'no_payment_required'];

    /**
     * Records the first payment of a completed Checkout session. Returns null for sessions that are unpaid or that
     * did not come from this app's donate page (the website's own donate form uses the same Stripe account).
     *
     * @param  array<string, mixed>  $session
     */
    public function fromCheckoutSession(array $session): ?Donation
    {
        $metadata = $session['metadata'] ?? [];

        if (! $this->isFromDonatePage($metadata) || ! in_array($session['payment_status'] ?? null, self::PAID_STATUSES, true)) {
            return null;
        }

        return $this->record(
            metadata: $metadata,
            amountPence: (int) ($session['amount_total'] ?? 0),
            reference: (string) $session['id'],
            paidAt: Carbon::createFromTimestamp((int) ($session['created'] ?? now()->getTimestamp())),
        );
    }

    /**
     * Records a monthly gift's later payments. The first month is recorded from its Checkout session instead.
     *
     * @param  array<string, mixed>  $invoice
     */
    public function fromRenewalInvoice(array $invoice): ?Donation
    {
        // Stripe moved subscription details under "parent" in API version 2025-03-31; older versions keep them on the invoice.
        $metadata = $invoice['parent']['subscription_details']['metadata'] ?? $invoice['subscription_details']['metadata'] ?? [];

        if (! $this->isFromDonatePage($metadata) || ($invoice['billing_reason'] ?? null) !== 'subscription_cycle' || ($invoice['status'] ?? null) !== 'paid') {
            return null;
        }

        return $this->record(
            metadata: $metadata,
            amountPence: (int) ($invoice['amount_paid'] ?? 0),
            reference: (string) $invoice['id'],
            paidAt: Carbon::createFromTimestamp((int) ($invoice['status_transitions']['paid_at'] ?? $invoice['created'] ?? now()->getTimestamp())),
        );
    }

    private function isFromDonatePage(mixed $metadata): bool
    {
        return is_array($metadata) && ($metadata['source'] ?? null) === StartOnlineDonation::SOURCE;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    private function record(array $metadata, int $amountPence, string $reference, CarbonInterface $paidAt): Donation
    {
        return Cache::lock("online-donation:{$reference}", 30)->block(10, fn (): Donation => DB::transaction(function () use ($metadata, $amountPence, $reference, $paidAt): Donation {
            $existing = Donation::query()->where('reference', $reference)->first();

            if ($existing instanceof Donation) {
                return $existing;
            }

            $givenOn = $paidAt->copy()->timezone((string) config('app.timezone'))->startOfDay();
            $donor = $this->donor($metadata);

            if (($metadata['gift_aid'] ?? '0') === '1' && ! $donor->giftAidDeclarationCovering($givenOn) instanceof GiftAidDeclaration) {
                GiftAidDeclaration::query()->create([
                    'team_id' => $donor->team_id,
                    'people_id' => $donor->id,
                    'declared_on' => $givenOn,
                    'method' => GiftAidDeclarationMethod::Online,
                    'covers_past_donations' => true,
                    'covers_future_donations' => true,
                    'address' => $this->address($metadata),
                    'postcode' => $metadata['postcode'] ?? null,
                    'notes' => 'Given on the online donate page.',
                ]);
            }

            $donation = new Donation([
                'team_id' => $donor->team_id,
                'amount' => $amountPence / 100,
                'donated_at' => $givenOn,
                'payment_method' => DonationPaymentMethod::Online,
                'purpose' => DonationPurpose::General,
                'reference' => $reference,
                'notes' => $this->notes($metadata),
            ]);
            $donation->donor()->associate($donor);
            $donation->save();

            return $donation;
        }));
    }

    /**
     * The person with this email in the team, or a new one. Details they left blank before are filled in, but
     * nothing staff already recorded is overwritten.
     *
     * @param  array<string, string>  $metadata
     */
    private function donor(array $metadata): People
    {
        $team = $this->team();
        $email = mb_strtolower(mb_trim($metadata['email'] ?? ''));
        $optedIn = ($metadata['contact_optin'] ?? '0') === '1';

        $person = People::query()
            ->where('team_id', $team->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('is_service_user')
            ->oldest('id')
            ->first();

        $details = array_filter([
            'phone' => $metadata['phone'] ?? null,
            'address' => $this->address($metadata),
            'postcode' => $metadata['postcode'] ?? null,
        ], filled(...));

        if (! $person instanceof People) {
            return People::query()->create([
                'team_id' => $team->id,
                'first_name' => $metadata['first_name'] ?? '',
                'last_name' => $metadata['last_name'] ?? '',
                'email' => $email,
                'consent_communications' => $optedIn,
                'creation_source' => CreationSource::WEB,
                ...$details,
            ]);
        }

        foreach ($details as $attribute => $value) {
            if (blank($person->{$attribute})) {
                $person->{$attribute} = $value;
            }
        }

        if ($optedIn) {
            $person->consent_communications = true;
        }

        $person->save();

        return $person;
    }

    private function team(): Team
    {
        $configured = config('services.stripe.team_id');

        $team = filled($configured)
            ? Team::query()->find($configured)
            : Team::query()->where('personal_team', false)->oldest('id')->first();

        if (! $team instanceof Team) {
            throw new RuntimeException('No team to record online donations against. Set STRIPE_DONATIONS_TEAM_ID.');
        }

        return $team;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    private function address(array $metadata): ?string
    {
        $firstLine = mb_trim(($metadata['house'] ?? '').' '.($metadata['street'] ?? ''));
        $address = implode(', ', array_filter([$firstLine, mb_trim($metadata['town'] ?? '')], filled(...)));

        return $address === '' ? null : $address;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    private function notes(array $metadata): string
    {
        $notes = ($metadata['frequency'] ?? '') === 'monthly'
            ? 'Monthly gift from the online donate page, paid through Stripe.'
            : 'One-off gift from the online donate page, paid through Stripe.';

        $feePence = (int) ($metadata['fee_pence'] ?? 0);

        if ($feePence > 0) {
            $notes .= ' Includes '.CardFee::format($feePence).' the donor added to cover card fees.';
        }

        if (filled($metadata['title'] ?? null)) {
            $notes .= " Title: {$metadata['title']}.";
        }

        return $notes;
    }
}
