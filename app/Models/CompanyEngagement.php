<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyEngagementType;
use App\Models\Concerns\HasTeam;
use Database\Factories\CompanyEngagementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A visit, talk, event or meeting with a company in the outreach directory.
 *
 * @property int $team_id
 * @property int $company_id
 * @property int|null $user_id
 * @property CompanyEngagementType $type
 * @property Carbon $occurred_on
 * @property string $summary
 */
#[Fillable(['team_id', 'company_id', 'user_id', 'type', 'occurred_on', 'summary'])]
final class CompanyEngagement extends Model
{
    /** @use HasFactory<CompanyEngagementFactory> */
    use HasFactory;

    use HasTeam;

    protected static function booted(): void
    {
        self::creating(function (CompanyEngagement $engagement): void {
            $engagement->team_id ??= $engagement->company?->team_id;
            $user = auth('web')->user();

            if ($user instanceof User) {
                $engagement->user_id ??= $user->id;
            }
        });
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => CompanyEngagementType::class,
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
