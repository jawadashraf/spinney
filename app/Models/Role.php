<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

final class Role extends SpatieRole
{
    use HasTeam;
    /**
     * @return BelongsTo<Team, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
