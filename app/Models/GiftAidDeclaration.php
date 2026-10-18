<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GiftAidDeclarationMethod;
use App\Enums\GiftAidStatus;
use App\Models\Concerns\HasTeam;
use Carbon\CarbonInterface;
use Database\Factories\GiftAidDeclarationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A donor's Gift Aid declaration. It can cover gifts from the four years before it was made, gifts from then on,
 * or both, until the donor withdraws it. The address is kept as declared because HMRC claims need it.
 *
 * @property int $team_id
 * @property int $people_id
 * @property int|null $creator_id
 * @property Carbon $declared_on
 * @property GiftAidDeclarationMethod $method
 * @property bool $covers_past_donations
 * @property bool $covers_future_donations
 * @property string $address
 * @property string|null $postcode
 * @property Carbon|null $withdrawn_on
 * @property string|null $notes
 */
#[Fillable([
    'team_id',
    'people_id',
    'declared_on',
    'method',
    'covers_past_donations',
    'covers_future_donations',
    'address',
    'postcode',
    'withdrawn_on',
    'notes',
])]
final class GiftAidDeclaration extends Model
{
    /** @use HasFactory<GiftAidDeclarationFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;

    public const int PAST_DONATION_YEARS = 4;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'covers_past_donations' => true,
        'covers_future_donations' => true,
    ];

    protected static function booted(): void
    {
        self::creating(function (GiftAidDeclaration $declaration): void {
            $declaration->team_id ??= $declaration->person?->team_id;
            $user = auth('web')->user();

            if ($user instanceof User) {
                $declaration->creator_id ??= $user->id;
            }
        });

        self::saved(function (GiftAidDeclaration $declaration): void {
            if ($declaration->wasChanged(['declared_on', 'covers_past_donations', 'covers_future_donations', 'withdrawn_on'])) {
                $declaration->recheckEligibleDonations();
            }
        });
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'declared_on' => 'date',
            'method' => GiftAidDeclarationMethod::class,
            'covers_past_donations' => 'boolean',
            'covers_future_donations' => 'boolean',
            'withdrawn_on' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['declared_on', 'method', 'covers_past_donations', 'covers_future_donations', 'withdrawn_on']);
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_on !== null && $this->withdrawn_on->lte(today());
    }

    /**
     * Whether a gift given on the date is covered: on the day it was made, after it if it covers future gifts, or
     * in the four years before it if it covers past gifts, and never on or after the day it was withdrawn.
     */
    public function covers(CarbonInterface $donatedAt): bool
    {
        if ($this->withdrawn_on !== null && $donatedAt->gte($this->withdrawn_on)) {
            return false;
        }

        if ($donatedAt->isSameDay($this->declared_on)) {
            return true;
        }

        if ($donatedAt->gt($this->declared_on)) {
            return $this->covers_future_donations;
        }

        return $this->covers_past_donations && $donatedAt->gte($this->declared_on->copy()->subYears(self::PAST_DONATION_YEARS));
    }

    /**
     * Unclaimed gifts this declaration no longer covers (after a withdrawal or a change to its dates) stay
     * eligible only if another declaration covers them. Claimed gifts are left alone.
     */
    private function recheckEligibleDonations(): void
    {
        $this->donations()
            ->where('gift_aid_status', GiftAidStatus::Eligible)
            ->get()
            ->reject(fn (Donation $donation): bool => $this->covers($donation->donated_at))
            ->each(fn (Donation $donation) => $donation->forceFill(['gift_aid_status' => null])->save());
    }
}
