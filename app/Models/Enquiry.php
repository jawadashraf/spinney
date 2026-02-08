<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnquiryCategory;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Enquiry extends Model
{
    use HasCreator;

    /** @use HasFactory<\Database\Factories\EnquiryFactory> */
    use HasFactory;
    use HasTeam;

    protected $fillable = [
        'people_id',
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
    ];

    protected function casts(): array
    {
        return [
            'category' => EnquiryCategory::class,
            'safeguarding_flags' => 'boolean',
            'occurred_at' => 'datetime',
        ];
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
}
