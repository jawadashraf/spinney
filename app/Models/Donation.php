<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A gift from a person or a company. Only gifts from people can be Gift Aid, and only while a declaration covers
 * the date they were given.
 *
 * @property int $team_id
 * @property string $donor_type
 * @property int $donor_id
 * @property int|null $gift_aid_declaration_id
 * @property int|null $creator_id
 * @property string $amount
 * @property Carbon $donated_at
 * @property DonationPaymentMethod $payment_method
 * @property DonationPurpose $purpose
 * @property GiftAidStatus|null $gift_aid_status
 * @property string|null $reference
 * @property string|null $notes
 * @property-read People|Company|null $donor
 */
#[Fillable([
    'team_id',
    'donor_type',
    'donor_id',
    'amount',
    'donated_at',
    'payment_method',
    'purpose',
    'gift_aid_status',
    'reference',
    'notes',
])]
final class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;

    protected static function booted(): void
    {
        self::creating(function (Donation $donation): void {
            $donation->team_id ??= $donation->donor?->team_id;
            $user = auth('web')->user();

            if ($user instanceof User) {
                $donation->creator_id ??= $user->id;
            }
        });

        self::saving(fn (Donation $donation) => $donation->applyGiftAidRules());
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'donated_at' => 'date',
            'payment_method' => DonationPaymentMethod::class,
            'purpose' => DonationPurpose::class,
            'gift_aid_status' => GiftAidStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['donor_type', 'donor_id', 'amount', 'donated_at', 'payment_method', 'purpose', 'gift_aid_status', 'gift_aid_declaration_id']);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function donor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<GiftAidDeclaration, $this>
     */
    public function giftAidDeclaration(): BelongsTo
    {
        return $this->belongsTo(GiftAidDeclaration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function donorName(): string
    {
        return $this->donor->name ?? 'Unknown donor';
    }

    /**
     * Company gifts are never Gift Aid. A gift from a person with no status chosen becomes eligible when a
     * declaration covers its date, and a Gift Aid gift is linked to the declaration that covers it.
     */
    private function applyGiftAidRules(): void
    {
        if (! $this->donor instanceof People) {
            $this->gift_aid_status = GiftAidStatus::NotEligible;
            $this->gift_aid_declaration_id = null;

            return;
        }

        $declaration = $this->donor->giftAidDeclarationCovering($this->donated_at);
        $this->gift_aid_status ??= $declaration instanceof GiftAidDeclaration ? GiftAidStatus::Eligible : GiftAidStatus::NotEligible;
        $this->gift_aid_declaration_id = $this->gift_aid_status->needsDeclaration() ? $declaration?->id : null;
    }
}
