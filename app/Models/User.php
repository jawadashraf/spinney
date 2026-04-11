<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CounselorType;
use App\Models\Concerns\HasProfilePhoto;
use Database\Factories\UserFactory;
use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Pivots\DepartmentUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
/**
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $profile_photo_path
 * @property-read string $profile_photo_url
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_secret
 */
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Zap\Models\Concerns\HasSchedules;

final class User extends Authenticatable implements FilamentUser, HasAvatar, HasTenants, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasSchedules;
    use HasTeams;
    use LogsActivity;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_system_admin',
        'counselor_types',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'profile_photo_url', // @phpstan-ignore rules.modelAppends
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_system_admin' => 'boolean',
            'counselor_types' => 'array',
        ];
    }

    public function hasSpecialty(CounselorType $type): bool
    {
        return in_array($type->value, $this->counselor_types ?? []);
    }

    public function addSpecialty(CounselorType $type): void
    {
        $types = $this->counselor_types ?? [];
        if (! in_array($type->value, $types)) {
            $types[] = $type->value;
            $this->counselor_types = $types;
            $this->save();
        }
    }

    public function removeSpecialty(CounselorType $type): void
    {
        $types = $this->counselor_types ?? [];
        $types = array_filter($types, fn ($t) => $t !== $type->value);
        $this->counselor_types = array_values($types);
        $this->save();
    }

    /**
     * @return HasMany<UserSocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'creator_id');
    }

    /**
     * @return HasOne<People, $this>
     */
    public function people(): HasOne
    {
        return $this->hasOne(People::class);
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->using(DepartmentUser::class)
            ->withPivot('team_id')
            ->withTimestamps();
    }

    /**
     * Determine if this user can impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Determine if this user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('super_admin');
    }

    /**
     * @throws Exception
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'sysadmin') {
            return $this->is_system_admin && $this->hasVerifiedEmail();
        }

        if ($panel->getId() === 'app' || $panel->getId() === 'knowledge-base') {
            if ($this->hasVerifiedEmail()) {
                return true;
            }

            return $this->hasRole('service_user');
        }

        return false;
    }

    /**
     * @return array<Model>|Collection<Model>
     */
    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->is_system_admin) {
            return Team::all();
        }

        return $this->allTeams();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->is_system_admin) {
            return true;
        }

        return $this->allTeams()->pluck('id')->contains($tenant->id);
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function team(): BelongsToMany
    {
        return $this->teams();
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function organization(): BelongsToMany
    {
        return $this->teams();
    }
}
