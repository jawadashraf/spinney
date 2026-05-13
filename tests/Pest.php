<?php

declare(strict_types=1);

ini_set('memory_limit', '1024M');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Team;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamCreated;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $dbDatabase = config('database.connections.mysql.database') ?? env('DB_DATABASE');
        if ($dbDatabase === 'spinney') {
            throw new Exception('CRITICAL: Test DB is "spinney" (production). Aborting to prevent data wipe. Run "php artisan config:clear" first.');
        }

        // Globally disable TeamCreated event to prevent demo record creation during tests
        // while allowing model observers to function.
        Event::fake([
            TeamCreated::class,
        ]);

        // Create a default team and owner for the seeder to use if it doesn't exist
        $user = User::factory()->create(['email' => 'system@spinney.test']);
        Team::factory()->create([
            'name' => 'Spinney Hill',
            'user_id' => $user->id,
            'personal_team' => false,
        ]);

        // Seed Shield roles so permission-based policies can function in tests.
        $this->seed(ShieldSeeder::class);
    })
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
