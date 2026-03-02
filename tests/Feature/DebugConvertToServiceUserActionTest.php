<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

it('is true', function () {
    // dd(config('database.connections.mysql.database'));
    expect(true)->toBeTrue();
});
