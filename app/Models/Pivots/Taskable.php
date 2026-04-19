<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

final class Taskable extends MorphPivot
{
    /**
     * @var string
     */
    protected $table = 'taskables';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'team_id' => 'integer',
        'task_id' => 'integer',
        'taskable_id' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Taskable $pivot): void {
            if (! $pivot->team_id) {
                if (auth()->check()) {
                    /** @var User $user */
                    $user = auth()->user();
                    $pivot->team_id = $user->current_team_id;
                } elseif ($pivot->task_id) {
                    $pivot->team_id = Task::find($pivot->task_id)?->team_id;
                }
            }
        });
    }
}
