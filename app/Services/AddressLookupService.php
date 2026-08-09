<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
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

        if ($postcode === '' || $postcode === '0') {
            return [];
        }

        // Always return mock addresses in testing environment unless overridden.
        if (app()->runningUnitTests() && ! config('services.ideal_postcodes.enable_http_tests_override')) {
            return $this->getMockAddresses($postcode);
        }

        $cleanPostcode = preg_replace('/[^A-Z0-9]/', '', $postcode) ?? $postcode;
        $ttl = (int) config('services.ideal_postcodes.cache_ttl', 86400);
        $cacheKey = "address_lookup:formatted:{$cleanPostcode}";

        if ($ttl > 0) {
            return Cache::remember($cacheKey, $ttl, fn (): array => $this->performLookup($postcode));
        }

        return $this->performLookup($postcode);
    }

    /**
     * Look up detailed, structured address options for a given UK postcode.
     *
     * @param  string  $postcode  The postcode to search for.
     * @return array<int, array<string, mixed>> List of structured address arrays.
     */
    public function lookupDetailed(string $postcode): array
    {
        $postcode = trim(strtoupper($postcode));

        if ($postcode === '' || $postcode === '0') {
            return [];
        }

        if (app()->runningUnitTests() && ! config('services.ideal_postcodes.enable_http_tests_override')) {
            return $this->getMockDetailedAddresses($postcode);
        }

        $cleanPostcode = preg_replace('/[^A-Z0-9]/', '', $postcode) ?? $postcode;
        $ttl = (int) config('services.ideal_postcodes.cache_ttl', 86400);
        $cacheKey = "address_lookup:detailed:{$cleanPostcode}";

        if ($ttl > 0) {
            return Cache::remember($cacheKey, $ttl, fn (): array => $this->performLookupDetailed($postcode));
        }

        return $this->performLookupDetailed($postcode);
    }

    /**
     * Internal handler to execute address lookup.
     *
     * @return array<string, string>
     */
    private function performLookup(string $postcode): array
    {
        $apiKey = config('services.ideal_postcodes.api_key');

        if (! empty($apiKey)) {
            return $this->lookupIdealPostcodes($postcode, (string) $apiKey);
        }

        return $this->lookupPostcodesIo($postcode);
    }

    /**
     * Internal handler to execute detailed address lookup.
     *
     * @return array<int, array<string, mixed>>
     */
    private function performLookupDetailed(string $postcode): array
    {
        $apiKey = config('services.ideal_postcodes.api_key');

        if (! empty($apiKey)) {
            return $this->lookupIdealPostcodesDetailed($postcode, (string) $apiKey);
        }

        return $this->lookupPostcodesIoDetailed($postcode);
    }

    /**
     * Look up full address options using Ideal Postcodes.
     *
     * @return array<string, string>
     */
    private function lookupIdealPostcodes(string $postcode, string $apiKey): array
    {
        $detailed = $this->lookupIdealPostcodesDetailed($postcode, $apiKey);

        if (! empty($detailed)) {
            $addresses = [];
            foreach ($detailed as $address) {
                $formatted = (string) ($address['formatted'] ?? '');
                if ($formatted !== '') {
                    $addresses[$formatted] = $formatted;
                }
            }

            return $addresses;
        }

        return $this->lookupPostcodesIo($postcode);
    }

    /**
     * Look up detailed address options using Ideal Postcodes API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lookupIdealPostcodesDetailed(string $postcode, string $apiKey): array
    {
        try {
            $baseUrl = rtrim((string) config('services.ideal_postcodes.base_url', 'https://api.ideal-postcodes.co.uk'), '/');
            $timeout = (int) config('services.ideal_postcodes.timeout', 5);
            $urlPostcode = rawurlencode($postcode);

            $response = Http::timeout($timeout)
                ->retry(2, 100)
                ->get("{$baseUrl}/v1/postcodes/{$urlPostcode}", [
                    'api_key' => $apiKey,
                ]);

            if ($response->successful()) {
                $result = $response->json('result') ?? [];
                $addresses = [];

                foreach ($result as $address) {
                    if (is_array($address)) {
                        $addresses[] = $this->transformIdealAddress($address);
                    }
                }

                return $addresses;
            }

            $message = $response->json('message') ?? 'HTTP status '.$response->status();
            Log::warning('Ideal Postcodes lookup returned error: '.$message);
        } catch (Exception $e) {
            Log::error('Ideal Postcodes exception: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Transform an Ideal Postcodes address block into a structured array.
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function transformIdealAddress(array $address): array
    {
        return [
            'line_1' => (string) ($address['line_1'] ?? ''),
            'line_2' => (string) ($address['line_2'] ?? ''),
            'line_3' => (string) ($address['line_3'] ?? ''),
            'post_town' => (string) ($address['post_town'] ?? ''),
            'county' => (string) ($address['county'] ?? ''),
            'country' => (string) ($address['country'] ?? 'United Kingdom'),
            'postcode' => (string) ($address['postcode'] ?? ''),
            'organisation_name' => (string) ($address['organisation_name'] ?? ''),
            'premise' => (string) ($address['premise'] ?? ''),
            'thoroughfare' => (string) ($address['thoroughfare'] ?? ''),
            'udprn' => $address['udprn'] ?? null,
            'formatted' => $this->formatIdealAddress($address),
        ];
    }

    /**
     * Format an Ideal Postcodes address block into a multi-line string.
     *
     * @param  array<string, mixed>  $address
     */
    private function formatIdealAddress(array $address): string
    {
        return collect([
            $address['organisation_name'] ?? '',
            $address['line_1'] ?? '',
            $address['line_2'] ?? '',
            $address['line_3'] ?? '',
            $address['post_town'] ?? '',
            $address['postcode'] ?? '',
        ])
            ->map(fn ($line): string => trim((string) $line))
            ->filter()
            ->unique()
            ->implode("\n");
    }

    /**
     * Look up city/town and region from postcodes.io (Free option).
     *
     * @return array<string, string>
     */
    private function lookupPostcodesIo(string $postcode): array
    {
        $detailed = $this->lookupPostcodesIoDetailed($postcode);
        if (! empty($detailed)) {
            $addresses = [];
            foreach ($detailed as $item) {
                $formatted = (string) ($item['formatted'] ?? '');
                if ($formatted !== '') {
                    $addresses[$formatted] = $formatted;
                }
            }

            if (! empty($addresses)) {
                return $addresses;
            }
        }

        return $this->getMockAddresses($postcode);
    }

    /**
     * Look up detailed location details from postcodes.io (Free option).
     *
     * @return array<int, array<string, mixed>>
     */
    private function lookupPostcodesIoDetailed(string $postcode): array
    {
        try {
            $timeout = (int) config('services.ideal_postcodes.timeout', 5);
            $urlPostcode = rawurlencode($postcode);

            $response = Http::timeout($timeout)
                ->get("https://api.postcodes.io/postcodes/{$urlPostcode}");

            if ($response->successful()) {
                $result = $response->json('result');

                if (is_array($result)) {
                    $town = (string) ($result['admin_district'] ?? $result['nhs_ha'] ?? '');
                    $region = (string) ($result['region'] ?? '');
                    $formattedPostcode = (string) ($result['postcode'] ?? $postcode);

                    $formatted = collect([
                        '',
                        $town,
                        $region,
                        $formattedPostcode,
                    ])
                        ->map(fn ($line): string => trim((string) $line))
                        ->filter()
                        ->implode("\n");

                    return [
                        [
                            'line_1' => '',
                            'line_2' => '',
                            'line_3' => '',
                            'post_town' => $town,
                            'county' => $region,
                            'country' => 'United Kingdom',
                            'postcode' => $formattedPostcode,
                            'organisation_name' => '',
                            'premise' => '',
                            'thoroughfare' => '',
                            'udprn' => null,
                            'formatted' => $formatted,
                        ],
                    ];
                }
            }
        } catch (Exception $e) {
            Log::error('Postcodes.io lookup exception: '.$e->getMessage());
        }

        return $this->getMockDetailedAddresses($postcode);
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

    /**
     * Get mock detailed addresses for testing/local development.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMockDetailedAddresses(string $postcode): array
    {
        return [
            [
                'line_1' => '10 Downing Street',
                'line_2' => '',
                'line_3' => '',
                'post_town' => 'London',
                'county' => 'Westminster',
                'country' => 'United Kingdom',
                'postcode' => $postcode,
                'organisation_name' => '',
                'premise' => '10',
                'thoroughfare' => 'Downing Street',
                'udprn' => 10001,
                'formatted' => "10 Downing Street\nWestminster\nLondon\n{$postcode}",
            ],
            [
                'line_1' => 'Flat 3',
                'line_2' => 'Baker Street',
                'line_3' => '',
                'post_town' => 'London',
                'county' => 'Marylebone',
                'country' => 'United Kingdom',
                'postcode' => $postcode,
                'organisation_name' => '',
                'premise' => '3',
                'thoroughfare' => 'Baker Street',
                'udprn' => 10002,
                'formatted' => "Flat 3, Baker Street\nMarylebone\nLondon\n{$postcode}",
            ],
            [
                'line_1' => '15 High Street',
                'line_2' => '',
                'line_3' => '',
                'post_town' => 'Manchester',
                'county' => 'City Centre',
                'country' => 'United Kingdom',
                'postcode' => $postcode,
                'organisation_name' => '',
                'premise' => '15',
                'thoroughfare' => 'High Street',
                'udprn' => 10003,
                'formatted' => "15 High Street\nCity Centre\nManchester\n{$postcode}",
            ],
            [
                'line_1' => 'Royal Albert Dock',
                'line_2' => '',
                'line_3' => '',
                'post_town' => 'Liverpool',
                'county' => '',
                'country' => 'United Kingdom',
                'postcode' => $postcode,
                'organisation_name' => '',
                'premise' => '',
                'thoroughfare' => 'Royal Albert Dock',
                'udprn' => 10004,
                'formatted' => "Royal Albert Dock\nLiverpool\n{$postcode}",
            ],
        ];
    }
}
