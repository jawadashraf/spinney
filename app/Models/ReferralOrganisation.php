<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferralPriority;
use App\Models\Concerns\HasTeam;
use App\Support\PhoneNumber;
use Database\Factories\ReferralOrganisationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $area_of_support
 * @property string $what_they_help_with
 * @property string|null $telephone
 * @property string|null $phone_normalized
 * @property string|null $email_or_text
 * @property string|null $website_url
 * @property string|null $address
 * @property string|null $how_to_refer
 * @property string|null $volunteer_notes
 * @property ReferralPriority $priority
 * @property string|null $last_checked_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 */
final class ReferralOrganisation extends Model
{
    /** @use HasFactory<ReferralOrganisationFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'area_of_support',
        'what_they_help_with',
        'telephone',
        'phone_normalized',
        'email_or_text',
        'website_url',
        'address',
        'how_to_refer',
        'volunteer_notes',
        'priority',
        'last_checked_at',
        'is_active',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'priority' => ReferralPriority::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $organisation): void {
            if (filled($organisation->telephone)) {
                $organisation->phone_normalized = PhoneNumber::normalize($organisation->telephone);
            } else {
                $organisation->phone_normalized = null;
            }
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
