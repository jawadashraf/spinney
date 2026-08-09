<?php

declare(strict_types=1);

use App\Services\AddressLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('it returns empty array for empty postcode', function () {
    $service = new AddressLookupService;

    expect($service->lookup(''))->toBeEmpty()
        ->and($service->lookup('   '))->toBeEmpty()
        ->and($service->lookupDetailed(''))->toBeEmpty();
});

test('it returns mock data by default in tests', function () {
    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');
    $detailed = $service->lookupDetailed('SW1A 2AA');

    expect($results)->toHaveCount(4);
    expect(array_keys($results)[0])->toContain('10 Downing Street');
    expect(array_keys($results)[0])->toContain('SW1A 2AA');

    expect($detailed)->toBeArray()->not->toBeEmpty();
    expect($detailed[0])->toHaveKeys(['line_1', 'line_2', 'post_town', 'postcode', 'formatted']);
});

test('it uses Ideal Postcodes API if key is configured and override is enabled', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => 'test-api-key']);
    config(['services.ideal_postcodes.cache_ttl' => 0]);

    Http::fake([
        'https://api.ideal-postcodes.co.uk/*' => Http::response([
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'London',
                    'postcode' => 'SW1A 2AA',
                    'organisation_name' => 'Prime Minister Office',
                ],
                [
                    'line_1' => '12 Downing Street',
                    'line_2' => 'Flat 2',
                    'line_3' => '',
                    'post_town' => 'London',
                    'postcode' => 'SW1A 2AA',
                ],
            ],
        ], 200),
    ]);

    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');

    expect($results)->toHaveCount(2);
    expect(array_keys($results))->toContain("Prime Minister Office\n10 Downing Street\nLondon\nSW1A 2AA");
    expect(array_keys($results))->toContain("12 Downing Street\nFlat 2\nLondon\nSW1A 2AA");

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://api.ideal-postcodes.co.uk/v1/postcodes/SW1A%202AA') &&
               $request['api_key'] === 'test-api-key';
    });
});

test('it supports lookupDetailed with structured data from Ideal Postcodes', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => 'test-api-key']);
    config(['services.ideal_postcodes.cache_ttl' => 0]);

    Http::fake([
        'https://api.ideal-postcodes.co.uk/*' => Http::response([
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'London',
                    'county' => 'Greater London',
                    'country' => 'England',
                    'postcode' => 'SW1A 2AA',
                    'organisation_name' => 'Prime Minister Office',
                    'premise' => '10',
                    'thoroughfare' => 'Downing Street',
                    'udprn' => 123456,
                ],
            ],
        ], 200),
    ]);

    $service = new AddressLookupService;
    $detailed = $service->lookupDetailed('SW1A 2AA');

    expect($detailed)->toHaveCount(1);
    expect($detailed[0]['line_1'])->toBe('10 Downing Street');
    expect($detailed[0]['post_town'])->toBe('London');
    expect($detailed[0]['county'])->toBe('Greater London');
    expect($detailed[0]['postcode'])->toBe('SW1A 2AA');
    expect($detailed[0]['organisation_name'])->toBe('Prime Minister Office');
    expect($detailed[0]['udprn'])->toBe(123456);
});

test('it caches lookup results when cache_ttl is set', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => 'test-api-key']);
    config(['services.ideal_postcodes.cache_ttl' => 86400]);

    Cache::flush();

    Http::fake([
        'https://api.ideal-postcodes.co.uk/*' => Http::response([
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'post_town' => 'London',
                    'postcode' => 'SW1A 2AA',
                ],
            ],
        ], 200),
    ]);

    $service = new AddressLookupService;

    // First call triggers HTTP request
    $results1 = $service->lookup('SW1A 2AA');
    expect($results1)->toHaveCount(1);

    // Second call should return cached result without hitting HTTP fake again
    $results2 = $service->lookup('SW1A 2AA');
    expect($results2)->toBe($results1);

    Http::assertSentCount(1);
});

test('it falls back to postcodes.io if api key is missing and override is enabled', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => null]);
    config(['services.ideal_postcodes.cache_ttl' => 0]);

    Http::fake([
        'https://api.postcodes.io/*' => Http::response([
            'result' => [
                'admin_district' => 'Westminster',
                'region' => 'London',
                'postcode' => 'SW1A 2AA',
            ],
        ], 200),
    ]);

    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');

    expect($results)->toHaveCount(1);
    expect(array_keys($results))->toContain("Westminster\nLondon\nSW1A 2AA");

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://api.postcodes.io/postcodes/SW1A%202AA');
    });
});

test('it falls back to mock data if postcodes.io fails', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => null]);
    config(['services.ideal_postcodes.cache_ttl' => 0]);

    Http::fake([
        'https://api.postcodes.io/*' => Http::response([], 500),
    ]);

    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');

    expect($results)->toHaveCount(4);
});
