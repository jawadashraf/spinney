<?php

declare(strict_types=1);

use App\Services\AddressLookupService;
use Illuminate\Support\Facades\Http;

test('it returns empty array for empty postcode', function () {
    $service = new AddressLookupService;
    expect($service->lookup(''))->toBeEmpty()
        ->and($service->lookup('   '))->toBeEmpty();
});

test('it returns mock data by default in tests', function () {
    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');

    expect($results)->toHaveCount(4);
    expect(array_keys($results)[0])->toContain('10 Downing Street');
    expect(array_keys($results)[0])->toContain('SW1A 2AA');
});

test('it uses Ideal Postcodes API if key is configured and override is enabled', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => 'test-api-key']);

    Http::fake([
        'https://api.ideal-postcodes.co.uk/*' => Http::response([
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'London',
                    'postcode' => 'SW1A 2AA',
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
    expect(array_keys($results))->toContain("10 Downing Street\nLondon\nSW1A 2AA");
    expect(array_keys($results))->toContain("12 Downing Street\nFlat 2\nLondon\nSW1A 2AA");

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://api.ideal-postcodes.co.uk/v1/postcodes/SW1A%202AA') &&
               $request['api_key'] === 'test-api-key';
    });
});

test('it falls back to postcodes.io if api key is missing and override is enabled', function () {
    config(['services.ideal_postcodes.enable_http_tests_override' => true]);
    config(['services.ideal_postcodes.api_key' => null]);

    Http::fake([
        'https://api.postcodes.io/*' => Http::response([
            'result' => [
                'admin_district' => 'Westminster',
                'region' => 'London',
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

    Http::fake([
        'https://api.postcodes.io/*' => Http::response([], 500),
    ]);

    $service = new AddressLookupService;
    $results = $service->lookup('SW1A 2AA');

    expect($results)->toHaveCount(4);
});
