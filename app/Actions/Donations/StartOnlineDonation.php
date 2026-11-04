<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Services\StripeCheckout;
use App\Support\CardFee;
use Illuminate\Http\Client\RequestException;

/**
 * Opens a Stripe Checkout session for a gift from the public donate page. Nothing is saved yet: the donor's details
 * travel in the session's metadata, and RecordOnlineDonation saves them once Stripe confirms the payment.
 */
final readonly class StartOnlineDonation
{
    public const string SOURCE = 'spinney_crm_donate_page';

    public function __construct(private StripeCheckout $stripe) {}

    /**
     * Returns the Stripe Checkout URL to send the donor to.
     *
     * @param  array{frequency: string, gift_pence: int, cover_fee: bool, title: ?string, first_name: string, last_name: string, email: string, phone: ?string, gift_aid: bool, house: ?string, street: ?string, town: ?string, postcode: ?string, contact_optin: bool}  $details
     *
     * @throws RequestException
     */
    public function handle(array $details): string
    {
        $isMonthly = $details['frequency'] === 'monthly';
        $feePence = $details['cover_fee'] ? CardFee::toCover($details['gift_pence']) : 0;

        $metadata = [
            'source' => self::SOURCE,
            'frequency' => $isMonthly ? 'monthly' : 'one-off',
            'gift_pence' => (string) $details['gift_pence'],
            'fee_pence' => (string) $feePence,
            'title' => (string) $details['title'],
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'email' => $details['email'],
            'phone' => (string) $details['phone'],
            'gift_aid' => $details['gift_aid'] ? '1' : '0',
            'house' => $details['gift_aid'] ? (string) $details['house'] : '',
            'street' => $details['gift_aid'] ? (string) $details['street'] : '',
            'town' => $details['gift_aid'] ? (string) $details['town'] : '',
            'postcode' => $details['gift_aid'] ? (string) $details['postcode'] : '',
            'contact_optin' => $details['contact_optin'] ? '1' : '0',
        ];

        $priceData = [
            'currency' => 'gbp',
            'unit_amount' => $details['gift_pence'] + $feePence,
            'product_data' => ['name' => $isMonthly ? 'Monthly donation to Spinney Hill' : 'Donation to Spinney Hill'],
        ];

        if ($isMonthly) {
            $priceData['recurring'] = ['interval' => 'month'];
        }

        $parameters = [
            'mode' => $isMonthly ? 'subscription' : 'payment',
            'customer_email' => $details['email'],
            'line_items' => [['price_data' => $priceData, 'quantity' => 1]],
            'metadata' => $metadata,
            // Stripe swaps in the session id itself, so the braces must reach it unencoded.
            'success_url' => route('donate.thank-you').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('donate', ['cancelled' => 1]),
        ];

        if ($isMonthly) {
            // Each month's invoice carries the subscription's metadata, so renewals can be recorded too.
            $parameters['subscription_data'] = ['metadata' => $metadata];
        } else {
            $parameters['submit_type'] = 'donate';
        }

        return (string) $this->stripe->createSession($parameters)['url'];
    }
}
