<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Enums\CreationSource;
use App\Models\Concerns\HasAiSummary;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasDonations;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\Pivots\Taskable;
use App\Observers\CompanyObserver;
use App\Services\AvatarService;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $name
 * @property int|null $company_type_id
 * @property int|null $parent_company_id
 * @property CompanyStatus $status
 * @property CompanyVerificationStatus $verification_status
 * @property Carbon|null $verified_at
 * @property string|null $verification_note
 * @property string|null $source
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property string|null $postcode
 * @property Carbon|null $email_invalid_at
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property-read string $created_by
 */
#[ObservedBy(CompanyObserver::class)]
#[Fillable([
    'name',
    'company_type_id',
    'parent_company_id',
    'status',
    'verification_status',
    'verification_note',
    'source',
    'email',
    'phone',
    'website',
    'address',
    'postcode',
    'email_invalid_at',
    'unsubscribed_at',
    'creation_source',
])]
final class Company extends Model implements HasCustomFieldsContract, HasMedia
{
    use HasAiSummary;
    use HasCreator;
    use HasCustomFields;
    use HasDonations;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'status' => CompanyStatus::Active,
        'verification_status' => CompanyVerificationStatus::Unverified,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'status' => CompanyStatus::class,
            'verification_status' => CompanyVerificationStatus::class,
            'verified_at' => 'datetime',
            'email_invalid_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Companies an outreach email could reach: a usable general email, or a contact on the mailing list with one.
     *
     * @param  Builder<Company>  $query
     */
    #[Scope]
    protected function reachableByEmail(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => $query
                ->whereNotNull('email')
                ->whereNull('email_invalid_at')
                ->whereNull('unsubscribed_at'))
            ->orWhereHas('contacts', fn (Builder $query) => $query
                ->where('consent_communications', true)
                ->whereNotNull('email')
                ->whereNull('email_invalid_at')
                ->whereNull('unsubscribed_at')));
    }

    public function getLogoAttribute(): string
    {
        $logo = $this->getFirstMediaUrl('logo');

        return $logo === '' || $logo === '0' ? app(AvatarService::class)->generateAuto(name: $this->name) : $logo;
    }

    /**
     * Team member responsible for managing the company account
     *
     * @return BelongsTo<User, $this>
     */
    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }

    /**
     * @return HasMany<People, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    /**
     * Named contacts at the company, such as an imam or head teacher.
     *
     * @return HasMany<Professional, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Professional::class);
    }

    /**
     * The organisation this one is part of, such as the masjid that runs a madrasah.
     *
     * @return BelongsTo<Company, $this>
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_company_id');
    }

    /**
     * Sites and services that are part of this organisation.
     *
     * @return HasMany<Company, $this>
     */
    public function linkedCompanies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_company_id');
    }

    /**
     * @return BelongsTo<CompanyType, $this>
     */
    public function companyType(): BelongsTo
    {
        return $this->belongsTo(CompanyType::class);
    }

    /**
     * @return BelongsToMany<MailingGroup, $this>
     */
    public function mailingGroups(): BelongsToMany
    {
        return $this->belongsToMany(MailingGroup::class);
    }

    /**
     * @return HasMany<CompanyEngagement, $this>
     */
    public function engagements(): HasMany
    {
        return $this->hasMany(CompanyEngagement::class);
    }

    /**
     * Outreach emails sent to this company's general email or its contacts.
     *
     * @return HasMany<OutreachEmailRecipient, $this>
     */
    public function outreachRecipients(): HasMany
    {
        return $this->hasMany(OutreachEmailRecipient::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * @return MorphToMany<Task, $this, Taskable>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable')
            ->using(Taskable::class)
            ->withPivot(['team_id'])
            ->withTimestamps();
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
