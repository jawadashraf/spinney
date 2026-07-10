<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

#[Table(name: 'noteables')]
final class Noteable extends MorphPivot
{
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'team_id' => 'integer',
        'note_id' => 'integer',
        'noteable_id' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Noteable $pivot): void {
            if (! $pivot->team_id && auth()->check()) {
                /** @var User $user */
                $user = auth()->user();
                $pivot->team_id = $user->current_team_id;
            }
        });
    }
}
