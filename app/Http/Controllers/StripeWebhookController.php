<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Donations\RecordOnlineDonation;
use App\Services\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stripe's webhook for gifts from the public donate page. It records a gift when the donor closes the tab before
 * reaching the thank-you page, and records each later month of a monthly gift.
 *
 * Subscribe the endpoint to checkout.session.completed, checkout.session.async_payment_succeeded and invoice.paid.
 */
final readonly class StripeWebhookController
{
    public function __invoke(Request $request, StripeCheckout $stripe, RecordOnlineDonation $recordDonation): Response
    {
        $event = $stripe->verifiedEvent($request->getContent(), $request->header('Stripe-Signature'));

        if ($event === null) {
            return response('Invalid signature.', 400);
        }

        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $recordDonation->fromCheckoutSession($object),
            'invoice.paid' => $recordDonation->fromRenewalInvoice($object),
            default => null,
        };

        return response('OK');
    }
}
