<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\CompanyTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A team-managed category for the outreach directory, such as Masjid or Madrassa.
 *
 * @property int $team_id
 * @property string $name
 */
#[Fillable(['team_id', 'name'])]
final class CompanyType extends Model
{
    /** @use HasFactory<CompanyTypeFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    public const array DEFAULTS = ['Charity / Partner', 'Education Institute', 'Masjid', 'Madrassa'];

    public static function createDefaultsFor(Team $team): void
    {
        foreach (self::DEFAULTS as $name) {
            self::query()->firstOrCreate(['team_id' => $team->getKey(), 'name' => $name]);
        }
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
