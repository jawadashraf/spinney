<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Donations\RecordOnlineDonation;
use App\Actions\Donations\StartOnlineDonation;
use App\Services\StripeCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * The page Stripe sends donors back to after paying.
 */
final readonly class DonationThankYouController
{
    /**
     * Records the gift as soon as the donor is back, rather than waiting for the webhook. The session is read
     * from Stripe, so a made-up session id cannot record anything.
     */
    public function __invoke(Request $request, StripeCheckout $stripe, RecordOnlineDonation $recordDonation): View|RedirectResponse
    {
        $sessionId = (string) $request->query('session_id');

        if (! str_starts_with($sessionId, 'cs_')) {
            return to_route('donate');
        }

        try {
            $session = $stripe->retrieveSession($sessionId);
        } catch (Throwable $exception) {
            report($exception);

            // The webhook still records the gift; the donor only misses the personal touch on this page.
            return view('donate.thank-you', ['firstName' => null, 'isMonthly' => false]);
        }

        $metadata = $session['metadata'] ?? [];

        if (($metadata['source'] ?? null) !== StartOnlineDonation::SOURCE) {
            return to_route('donate');
        }

        $recordDonation->fromCheckoutSession($session);

        return view('donate.thank-you', [
            'firstName' => $metadata['first_name'] ?? null,
            'isMonthly' => ($metadata['frequency'] ?? null) === 'monthly',
        ]);
    }
}
