<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\MailingGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A tag such as "Newsletter" used to pick which companies receive an email.
 *
 * @property int $team_id
 * @property string $name
 */
#[Fillable(['team_id', 'name'])]
final class MailingGroup extends Model
{
    /** @use HasFactory<MailingGroupFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }
}
