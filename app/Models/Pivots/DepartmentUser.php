<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class DepartmentUser extends Pivot
{
    /**
     * @var string
     */
    protected $table = 'department_user';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'team_id' => 'integer',
        'department_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (DepartmentUser $pivot): void {
            if (! $pivot->team_id && auth()->check()) {
                /** @var User $user */
                $user = auth()->user();
                $pivot->team_id = $user->current_team_id;
            }
        });
    }
}
