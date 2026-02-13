<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CustomField;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

final class LocalSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command->info('Skipping local seeding as the environment is not local.');

            return;
        }

        
        $this->call([
            SystemAdministratorSeeder::class,
            TeamSeeder::class,
        ]);
        

        $user = User::firstOrCreate(
            ['email' => 'manuk.minasyan1@gmail.com'],
            [
                'name' => 'Manuk Minasyan',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        


        if ($user->wasRecentlyCreated && ! $user->currentTeam) {
            $managementTeam = Team::where('name', 'Management')->first();
            if ($managementTeam) {
                $user->teams()->attach($managementTeam, ['role' => 'admin']);
                $user->switchTeam($managementTeam);
            }
        }

        $teamId = $user->currentTeam?->id;

        if ($teamId) {
            // Create 10 Test Users
            User::factory()
                ->count(10)
                ->create()
                ->after(function (User $user) use ($teamId): void {
                    // Assign the user to the personal team.
                    $user->teams()->attach($teamId, [
                        'role' => 'member',
                    ]);
                });
        }
        if ($user->currentTeam) {
            // Set the current user and tenant context for any observers or logic that depends on it.
            Auth::setUser($user);
            Filament::setTenant($user->currentTeam);

            $customFields = CustomField::query()
                ->whereIn('code', ['icp', 'stage', 'domain_name'])
                ->get()
                ->keyBy('code');

            // Create companies.
            Company::factory()
                ->for($user->currentTeam, 'team')
                ->count(50)
                ->afterCreating(function (Company $company) use ($customFields): void {
                    if ($field = $customFields->get('domain_name')) {
                        $company->saveCustomFieldValue($field, 'https://'.fake()->domainName());
                    }
                    if ($field = $customFields->get('icp')) {
                        $company->saveCustomFieldValue($field, fake()->boolean(70));
                    }
                })
                ->create();

            // Create people.
            People::factory()
                ->for($user->currentTeam, 'team')
                ->count(500)
                ->afterCreating(function (People $person) use ($user): void {
                    $company = $user->currentTeam->companies()->inRandomOrder()->first();
                    if ($company) {
                        $person->update(['company_id' => $company->id]);
                    }
                })
                ->create();

            // Create opportunities.
            Opportunity::factory()
                ->for($user->currentTeam, 'team')
                ->count(150)
                ->afterCreating(function (Opportunity $opportunity) use ($customFields): void {
                    if ($field = $customFields->get('stage')) {
                        $option = $field->options()->inRandomOrder()->first();
                        if ($option) {
                            $opportunity->saveCustomFieldValue($field, $option->id);
                        }
                    }
                })
                ->create();
        }
    }
}
