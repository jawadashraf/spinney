<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The few Stripe API calls the public donation page needs. Stripe Checkout takes the card details, so this app
 * never handles them: it opens a Checkout session, reads one back, and checks that webhooks really came from Stripe.
 */
final readonly class StripeCheckout
{
    private const string API_URL = 'https://api.stripe.com/v1';

    private const int SIGNATURE_TOLERANCE_SECONDS = 300;

    /**
     * Opens a Checkout session and returns it; the donor is sent to its `url`.
     *
     * @param  array<string, mixed>  $parameters  Stripe's Checkout Session parameters.
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function createSession(array $parameters): array
    {
        return Http::asForm()
            ->withToken($this->secret())
            ->post(self::API_URL.'/checkout/sessions', $parameters)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function retrieveSession(string $sessionId): array
    {
        return Http::withToken($this->secret())
            ->get(self::API_URL.'/checkout/sessions/'.rawurlencode($sessionId))
            ->throw()
            ->json();
    }

    /**
     * Returns the webhook event when the Stripe-Signature header matches the payload, or null when it does not.
     *
     * @return array<string, mixed>|null
     */
    public function verifiedEvent(string $payload, ?string $signatureHeader): ?array
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret) || blank($signatureHeader)) {
            return null;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', mb_trim($part), 2), 2, '');

            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || abs(now()->getTimestamp() - $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return null;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $event = json_decode($payload, true);

                return is_array($event) ? $event : null;
            }
        }

        return null;
    }

    private function secret(): string
    {
        $secret = config('services.stripe.secret');

        if (blank($secret)) {
            throw new RuntimeException('STRIPE_SECRET is not set, so online donations cannot reach Stripe.');
        }

        return (string) $secret;
    }
}
