<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stops a staff member signing in without deleting them, so the notes, calls and appointments they worked on keep
 * their name. Any session they have open, "remember me" cookie and API token stop working straight away.
 */
final class DeactivateUser
{
    public function handle(User $user): void
    {
        if ($user->isDeactivated()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'deactivated_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }
        });
    }
}
