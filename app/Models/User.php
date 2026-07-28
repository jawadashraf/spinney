<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CounselorType;
use App\Models\Concerns\HasProfilePhoto;
use App\Models\Pivots\DepartmentUser;
use App\Models\Pivots\TaskUser;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Exception;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
/**
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $profile_photo_path
 * @property-read string $profile_photo_url
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property string|null $two_factor_secret
 * @property array<int, string> $counselor_types
 *
 * @method array getBookableSlots(string $date, int $duration)
 */
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Zap\Data\FrequencyConfig;
use Zap\Models\Concerns\HasSchedules;

#[Appends([
    'name',
    'profile_photo_url',
])]
#[Fillable([
    'name',
    'first_name',
    'last_name',
    'email',
    'password',
    'is_system_admin',
    'counselor_types',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
])]
final class User extends Authenticatable implements FilamentUser, HasAvatar, HasTenants, MustVerifyEmail
{
    use HasActivity;
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles {
        HasRoles::teams as permissionTeams;
    }
    use HasSchedules;
    use HasTeams {
        HasTeams::teams insteadof HasRoles;
    }
    use Notifiable;
    use TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

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

    public function getNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }

        return $this->attributes['name'] ?? '';
    }

    protected static function booted(): void
    {
        self::saving(function (User $user): void {
            if (isset($user->attributes['name']) && ! isset($user->attributes['first_name'])) {
                $nameParts = explode(' ', trim((string) $user->attributes['name']), 2);
                $user->first_name = $nameParts[0];
                $user->last_name = $nameParts[1] ?? $nameParts[0];
            }

            if ($user->first_name && $user->last_name) {
                $user->attributes['name'] = "{$user->first_name} {$user->last_name}";
            }

            if ($user->first_name && $user->last_name) {
                $user->attributes['name'] = "{$user->first_name} {$user->last_name}";
            }
        });
    }

    public function isVolunteerLiaison(): bool
    {
        if (class_exists(Filament::class)) {
            $team = Filament::getTenant() ?? $this->currentTeam ?? $this->teams()->first() ?? auth()->user()->currentTeam ?? Team::first();
            if ($team) {
                setPermissionsTeamId($team->getKey());
            }
        }

        return $this->hasRole('volunteer_liaison');
    }

    public function isRestrictedVolunteerLiaison(): bool
    {
        return $this->isVolunteerLiaison()
            && $this->roles->where('name', '!=', 'volunteer_liaison')->isEmpty();
    }

    public function isWithinWorkHours(?CarbonInterface $at = null): bool
    {
        $at = $at instanceof CarbonInterface ? Carbon::parse($at->format('Y-m-d H:i:s'), 'Europe/London') : Carbon::now('Europe/London');
        $dateStr = $at->format('Y-m-d');
        $timeStr = $at->format('H:i');

        $availabilitySchedules = $this->schedules()
            ->active()
            ->availability()
            ->forDate($dateStr)
            ->with('periods')
            ->get();

        foreach ($availabilitySchedules as $schedule) {
            if (! ($schedule->metadata['is_approved'] ?? false)) {
                continue;
            }

            if (! $schedule->isActiveOn($dateStr)) {
                continue;
            }

            if ($schedule->is_recurring) {
                $config = $schedule->frequency_config;
                if ($config instanceof FrequencyConfig && ! $config->shouldCreateRecurringInstance($schedule, $at)) {
                    continue;
                }
            }

            foreach ($schedule->periods as $period) {
                if (! $period->start_time) {
                    continue;
                }
                if (! $period->end_time) {
                    continue;
                }
                $startTime = substr((string) $period->start_time, 0, 5);
                $endTime = substr((string) $period->end_time, 0, 5);

                if ($timeStr >= $startTime && $timeStr <= $endTime) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getWeeklyScheduledMinutes(string $startDate, string $endDate): int
    {
        return $this->getTotalScheduledTime($startDate, $endDate);
    }

    public function hasSpecialty(CounselorType $type): bool
    {
        /** @var array<int, string> $types */
        $types = $this->counselor_types ?? [];

        return in_array($type->value, $types);
    }

    public function addSpecialty(CounselorType $type): void
    {
        /** @var array<int, string> $types */
        $types = $this->counselor_types ?? [];
        if (! in_array($type->value, $types)) {
            $types[] = $type->value;
            $this->forceFill(['counselor_types' => $types]);
            $this->save();
        }
    }

    public function removeSpecialty(CounselorType $type): void
    {
        /** @var array<int, string> $types */
        $types = $this->counselor_types ?? [];
        $types = array_filter($types, fn (string $t): bool => $t !== $type->value);
        $this->forceFill(['counselor_types' => array_values($types)]);
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
     * @return BelongsToMany<Task, $this, TaskUser>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->using(TaskUser::class)
            ->withPivot('team_id')
            ->withTimestamps();
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
     * @return BelongsToMany<Department, $this, DepartmentUser>
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
    /**
     * @return Collection<int, Team>
     */
    public function getTenants(Panel $panel): Collection
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

        return $this->allTeams()->pluck('id')->contains($tenant->getKey());
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
