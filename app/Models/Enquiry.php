<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryOutcome;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\EnquiryObserver;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(EnquiryObserver::class)]
final class Enquiry extends Model
{
    use HasCreator;

    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    use HasTeam;

    protected $fillable = [
        'people_id',
        'phone',
        'caller_note',
        'category',
        'reason_for_contact',
        'risk_flags',
        'safeguarding_flags',
        'advice_given',
        'action_taken',
        'referral_type',
        'referral_destination',
        'user_id',
        'occurred_at',
        'team_id',
        'creator_id',
        'status',
        'converted_at',
        'source',
        'direction',
        'call_type',
        'caller_type',
        'department_id',
        'due_date',
        'parent_enquiry_id',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'category' => EnquiryCategory::class,
            'status' => EnquiryStatus::class,
            'safeguarding_flags' => 'boolean',
            'occurred_at' => 'datetime',
            'converted_at' => 'datetime',
            'source' => EnquirySourceType::class,
            'direction' => EnquiryDirection::class,
            'call_type' => EnquiryCallType::class,
            'caller_type' => CallerType::class,
            'due_date' => 'datetime',
            'outcome' => EnquiryOutcome::class,
        ];
    }

    public function canBeConverted(): bool
    {
        return $this->status === EnquiryStatus::OPEN && $this->people_id !== null;
    }

    public function isOutbound(): bool
    {
        return $this->direction === EnquiryDirection::OUTBOUND;
    }

    public function isEmergency(): bool
    {
        return $this->call_type === EnquiryCallType::EMERGENCY;
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Enquiry, $this>
     */
    public function parentEnquiry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_enquiry_id');
    }

    /**
     * @return HasMany<Enquiry, $this>
     */
    public function childEnquiries(): HasMany
    {
        return $this->hasMany(self::class, 'parent_enquiry_id');
    }

    protected static function booted(): void
    {
        self::saving(function (Enquiry $enquiry): void {
            if ($enquiry->call_type === EnquiryCallType::EMERGENCY) {
                $enquiry->safeguarding_flags = true;
            }

            if ($enquiry->people_id === null) {
                $enquiry->caller_type = CallerType::ANONYMOUS;
            } else {
                $person = People::withTrashed()->find($enquiry->people_id);
                if ($person?->is_service_user) {
                    $enquiry->caller_type = CallerType::SERVICE_USER;
                } else {
                    $enquiry->caller_type = CallerType::KNOWN_PERSON;
                }
            }
        });
    }
}
