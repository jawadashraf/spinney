<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

/**
 * Lets a deactivated staff member sign in again with their existing password and roles.
 */
final class ReactivateUser
{
    public function handle(User $user): void
    {
        $user->forceFill(['deactivated_at' => null])->save();
    }
}
