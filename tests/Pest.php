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

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Support\CompanyPermissions;
use App\Support\DonationPermissions;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamCreated;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

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
        $team = Team::factory()->create([
            'name' => 'Spinney Hill',
            'user_id' => $user->id,
            'personal_team' => false,
        ]);

        static $listenerRegistered = false;
        if (! $listenerRegistered) {
            $listenerRegistered = true;
            User::created(function (User $user): void {
                if (! $user->is_system_admin) {
                    $team = Team::where('name', 'Spinney Hill')->first();
                    if (! $team) {
                        return;
                    }
                    if ($user->id !== $team->user_id) {
                        $isFilamentTest = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30))
                            ->pluck('class')
                            ->filter()
                            ->contains(fn (string $class): bool => str_contains($class, 'Tests\\Feature\\Filament'));

                        if ($isFilamentTest) {
                            $user->current_team_id = $team->id;
                            $user->saveQuietly();
                            $user->teams()->syncWithoutDetaching([$team->id => ['role' => 'admin']]);
                        }
                    }
                }
            });
        }

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

/**
 * Configure the Yeastar integration for a team and return the PBX's basic-auth header.
 *
 * @return array{Authorization: string}
 */
function yeastarHeaders(int $teamId, string $apiKey = 'test-yeastar-key'): array
{
    config([
        'services.yeastar.api_key' => $apiKey,
        'services.yeastar.team_id' => $teamId,
    ]);

    return ['Authorization' => 'Basic '.base64_encode($apiKey)];
}

/**
 * Sign in as a new member of the team with the given role, with the team as the current tenant.
 */
function signInToDirectoryAs(Team $team, string $role): User
{
    Role::firstOrCreate(['name' => $role, 'team_id' => $team->id], ['guard_name' => 'web']);
    CompanyPermissions::ensure($team->id);

    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->syncWithoutDetaching([$team->id => ['role' => 'member']]);
    $user->assignRole($role);

    actingAs($user);
    Filament::setTenant($team);

    return $user;
}

/**
 * Sign in as a new member of the team with the given role, with donation permissions granted as they are live.
 */
function signInToDonationsAs(Team $team, string $role): User
{
    Role::firstOrCreate(['name' => $role, 'team_id' => $team->id], ['guard_name' => 'web']);
    DonationPermissions::ensure($team->id);

    return signInToDirectoryAs($team, $role);
}
