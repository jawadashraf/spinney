<?php

declare(strict_types=1);

use App\Support\PhoneNumber;

it('normalises UK numbers to E.164', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('+447438356414');
})->with([
    'national' => '07438356414',
    'national with spaces' => '07438 356 414',
    'international' => '+447438356414',
    'international with spaces' => '+44 7438 356414',
    'international with trunk zero' => '+44 (0)7438 356414',
    'international dialling prefix' => '00447438356414',
    'country code without plus' => '447438356414',
    'leading zero dropped' => '7438356414',
    'dashes and dots' => '07438-356.414',
]);

it('keeps numbers from other countries', function () {
    expect(PhoneNumber::normalize('+1 (415) 555-0100'))->toBe('+14155550100');
});

it('leaves extensions as plain digits', function () {
    expect(PhoneNumber::normalize('1001'))->toBe('1001');
});

it('returns null for an empty number', function (?string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull();
})->with([
    'null' => null,
    'blank' => '   ',
    'no digits' => 'n/a',
]);

it('turns a partial national number into digits found in stored numbers', function () {
    expect(PhoneNumber::searchFragment('07438 35'))->toBe('743835');
});

it('does not search on fewer than three digits', function () {
    expect(PhoneNumber::searchFragment('07'))->toBeNull();
});
