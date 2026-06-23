<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class AddressLookupService
{
    /**
     * Look up matching addresses for a given UK postcode.
     *
     * @param  string  $postcode  The postcode to search for.
     * @return array<string, string> Key/value pairs of formatted address strings.
     */
    public function lookup(string $postcode): array
    {
        $postcode = trim(strtoupper($postcode));

        if (empty($postcode)) {
            return [];
        }

        // Always return mock addresses in testing environment unless overridden.
        if (app()->runningUnitTests() && ! config('services.ideal_postcodes.enable_http_tests_override')) {
            return $this->getMockAddresses($postcode);
        }

        $apiKey = config('services.ideal_postcodes.api_key');

        if (! empty($apiKey)) {
            return $this->lookupIdealPostcodes($postcode, $apiKey);
        }

        return $this->lookupPostcodesIo($postcode);
    }

    /**
     * Look up full address options using Ideal Postcodes.
     *
     * @return array<string, string>
     */
    private function lookupIdealPostcodes(string $postcode, string $apiKey): array
    {
        try {
            $response = Http::get("https://api.ideal-postcodes.co.uk/v1/postcodes/{$postcode}", [
                'api_key' => $apiKey,
            ]);

            if ($response->successful()) {
                $result = $response->json('result') ?? [];
                $addresses = [];

                foreach ($result as $address) {
                    $formatted = $this->formatIdealAddress($address);
                    if ($formatted !== '') {
                        $addresses[$formatted] = $formatted;
                    }
                }

                return $addresses;
            }

            Log::warning('Ideal Postcodes lookup returned error: '.$response->status());
        } catch (Exception $e) {
            Log::error('Ideal Postcodes exception: '.$e->getMessage());
        }

        // Fall back to free lookup on failure
        return $this->lookupPostcodesIo($postcode);
    }

    /**
     * Format an Ideal Postcodes address block.
     *
     * @param  array<string, mixed>  $address
     */
    private function formatIdealAddress(array $address): string
    {
        return collect([
            $address['line_1'] ?? '',
            $address['line_2'] ?? '',
            $address['line_3'] ?? '',
            $address['post_town'] ?? '',
            $address['postcode'] ?? '',
        ])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->implode("\n");
    }

    /**
     * Look up city/town and region from postcodes.io (Free option).
     *
     * @return array<string, string>
     */
    private function lookupPostcodesIo(string $postcode): array
    {
        try {
            $response = Http::get("https://api.postcodes.io/postcodes/{$postcode}");

            if ($response->successful()) {
                $result = $response->json('result');

                if (is_array($result)) {
                    $town = $result['admin_district'] ?? $result['nhs_ha'] ?? '';
                    $region = $result['region'] ?? '';

                    $formatted = collect([
                        '', // Street line placeholder
                        $town,
                        $region,
                        $postcode,
                    ])
                        ->map(fn ($line) => trim((string) $line))
                        ->filter()
                        ->implode("\n");

                    return [$formatted => $formatted];
                }
            }
        } catch (Exception $e) {
            Log::error('Postcodes.io lookup exception: '.$e->getMessage());
        }

        // Fall back to mock addresses if even the free API fails
        return $this->getMockAddresses($postcode);
    }

    /**
     * Get mock addresses for testing/local development.
     *
     * @return array<string, string>
     */
    private function getMockAddresses(string $postcode): array
    {
        $addresses = [
            "10 Downing Street\nWestminster\nLondon\n{$postcode}",
            "Flat 3, Baker Street\nMarylebone\nLondon\n{$postcode}",
            "15 High Street\nCity Centre\nManchester\n{$postcode}",
            "Royal Albert Dock\nLiverpool\n{$postcode}",
        ];

        return array_combine($addresses, $addresses);
    }
}
