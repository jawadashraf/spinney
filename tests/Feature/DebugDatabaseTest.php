<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('uses the correct database', function () {
    $dbName = DB::connection()->getDatabaseName();
    $envDatabase = env('DB_DATABASE');
    $configDatabase = config('database.connections.mysql.database');

    echo "\nActual DB Name: " . $dbName . "\n";
    echo "Env DB Name: " . $envDatabase . "\n";
    echo "Config DB Name: " . $configDatabase . "\n";
    echo "APP_ENV: " . app()->environment() . "\n";

    expect($dbName)->toBe('spinney_test');
});
