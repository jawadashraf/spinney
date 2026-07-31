<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profile = \App\Models\ServiceUserProfile::latest()->first();
if ($profile) {
    echo "Profile ID: " . $profile->id . "\n";
    echo "Profile Team ID: " . $profile->team_id . "\n";
    echo "Person ID: " . $profile->person_id . "\n";
    echo "Person Team ID: " . $profile->person->team_id . "\n";
} else {
    echo "No profile found\n";
}
