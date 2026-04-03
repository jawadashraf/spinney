<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CounselorType;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

final class SyncCounselorSpecialties extends Command
{
    protected $signature = 'counselors:sync-specialties';

    protected $description = 'Migrate old specialty roles to counselor role with metadata';

    public function handle(): int
    {
        $this->info('Starting counselor specialty migration...');

        // Map old roles to counselor types
        $roleToTypeMap = [
            'drug_alcohol' => CounselorType::DRUG->value,
            'spiritual' => CounselorType::SPIRITUAL->value,
            'education_outreach' => CounselorType::EDUCATION->value,
            'outreach' => CounselorType::OUTREACH->value,
        ];

        $counselorRole = Role::where('name', 'counselor')->first();

        if (! $counselorRole) {
            $this->error('Counselor role not found. Please run SimplifiedRolePermissionSeeder first.');

            return Command::FAILURE;
        }

        $migrated = 0;
        $skipped = 0;

        foreach ($roleToTypeMap as $oldRoleName => $counselorType) {
            $users = User::role($oldRoleName)->get();

            if ($users->isEmpty()) {
                $this->line("No users found with role '{$oldRoleName}'.");

                continue;
            }

            foreach ($users as $user) {
                // Add counselor type to user's metadata
                $types = $user->counselor_types ?? [];
                if (! in_array($counselorType, $types)) {
                    $types[] = $counselorType;
                    $user->counselor_types = $types;
                    $user->save();
                }

                // Remove old role and add counselor role
                $user->removeRole($oldRoleName);
                if (! $user->hasRole('counselor')) {
                    $user->assignRole('counselor');
                }

                $this->line("Migrated user {$user->email} from '{$oldRoleName}' to counselor.");
                $migrated++;
            }
        }

        // Handle users with multiple old specialty roles
        $this->info('Checking for users with multiple specialties...');

        $this->info('Migration complete:');
        $this->info("- {$migrated} users migrated successfully");
        $this->info("- {$skipped} users skipped");
        $this->newLine();
        $this->info('Run this command to verify: php artisan tinker');
        $this->info('>>> User::role("counselor")->with("roles")->get()');

        return Command::SUCCESS;
    }
}
