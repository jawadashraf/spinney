<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('confirms which database is written to', function () {

    $dbName = DB::connection()->getDatabaseName();
    echo "\nTest process says it is using: ".$dbName."\n";

    // Create a user with a very unique email
    $uniqueEmail = 'test_check_'.time().'@example.com';

    User::create([
        'name' => 'Test User',
        'email' => $uniqueEmail,
        'password' => bcrypt('password'),
    ]);

    echo 'Created user with email: '.$uniqueEmail."\n";

    // We expect this to be in spinney_test
});
