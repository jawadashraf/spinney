# Quick Start Guide

## Setup Checklist

### 1. ✅ Run Shield Commands

```bash
# Generate permissions for all resources
php artisan shield:generate --all --ignore-existing-policies

# Create permissions in database
php artisan shield:se --all
```

### 2. ✅ Assign Permissions to Roles

Run in tinker or create a seeder:

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Get all schedule permissions
$schedulePermissions = Permission::where('name', 'like', '%schedule%')->get();

// Assign to roles
Role::where('name', 'drug_alcohol')->first()->givePermissionTo([
    'view_any_schedule',
    'view_schedule', 
    'create_schedule',
    'update_schedule',
    'delete_schedule'
]);

Role::where('name', 'spiritual')->first()->givePermissionTo($schedulePermissions->where('name', '!=', 'lock_schedule'));
Role::where('name', 'education_outreach')->first()->givePermissionTo($schedulePermissions->where('name', '!=', 'lock_schedule'));

// Management can lock/unlock
Role::where('name', 'management')->first()->givePermissionTo($schedulePermissions);
Role::where('name', 'management')->first()->givePermissionTo(['lock_schedule', 'unlock_schedule']);

// Admin has full access
Role::where('name', 'admin')->first()->givePermissionTo(Permission::all());

// Frontline - view only
Role::where('name', 'frontline')->first()->givePermissionTo([
    'view_any_schedule',
    'view_schedule'
]);

// Assessment - view only
Role::where('name', 'assessment')->first()->givePermissionTo([
    'view_any_schedule',
    'view_schedule'
]);

// Aftercare - view only
Role::where('name', 'aftercare')->first()->givePermissionTo([
    'view_any_schedule',
    'view_schedule'
]);

// Safeguarding - view only
Role::where('name', 'safeguarding')->first()->givePermissionTo([
    'view_any_schedule',
    'view_schedule'
]);
```

### 3. ✅ Create Test Data

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Enums\CounselorType;
use Zap\Facades\Zap;

// Create a drug counselor
$counselor = User::first(); // Or create a new user
$counselor->assignRole('drug_alcohol');

// Create weekly availability
Zap::for($counselor)
    ->named('Weekly Office Hours - Drug Counseling')
    ->availability()
    ->forYear(2025)
    ->addPeriod('09:00', '12:00')
    ->addPeriod('14:00', '17:00')
    ->weekly(['monday', 'wednesday', 'friday'])
    ->withMetadata([
        'team_id' => $counselor->current_team_id ?? 1,
        'counselor_type' => CounselorType::DRUG->value,
        'slot_duration_minutes' => 60,
        'capacity' => 1,
        'is_locked' => false,
    ])
    ->save();

// Create a spiritual counselor availability
$spiritualCounselor = User::find(2); // Or create another user
$spiritualCounselor->assignRole('spiritual');

Zap::for($spiritualCounselor)
    ->named('Spiritual Counseling Hours')
    ->availability()
    ->from('2025-04-07')
    ->to('2025-04-11')
    ->addPeriod('10:00', '15:00')
    ->daily()
    ->withMetadata([
        'team_id' => $spiritualCounselor->current_team_id ?? 1,
        'counselor_type' => CounselorType::SPIRITUAL->value,
        'slot_duration_minutes' => 60,
        'capacity' => 1,
        'is_locked' => false,
    ])
    ->save();
```

### 4. ✅ Test the System

1. **Login as a counselor** (e.g., `drug_alcohol` role)
   - Go to `/admin/schedules`
   - Click "Create"
   - Fill in the form
   - Save

2. **Login as a manager** (`management` role)
   - Go to `/admin/schedules`
   - Filter by `schedule_type` = "availability"
   - Find available slots
   - Create an appointment

3. **Check notifications**
   - Check database: `SELECT * FROM notifications;`
   - Check email logs

---

## Role-to-CounselorType Mapping

In your Filament resource, add a method to automatically set counselor type based on user role:

```php
// In ScheduleForm.php
Select::make('metadata.counselor_type')
    ->label('Counselor Type')
    ->options(CounselorType::class)
    ->required()
    ->native(false)
    ->default(function () {
        $user = auth()->user();
        
        if ($user->hasRole('drug_alcohol')) {
            return CounselorType::DRUG->value;
        }
        if ($user->hasRole('spiritual')) {
            return CounselorType::SPIRITUAL->value;
        }
        if ($user->hasRole('education_outreach')) {
            return CounselorType::EDUCATION->value; // or OUTREACH
        }
        
        return null;
    })
    ->disabled(fn () => auth()->user()->hasAnyRole(['drug_alcohol', 'spiritual', 'education_outreach'])),
```

---

## Automatic Role-Based Defaults

Add this to your Schedule model or resource to auto-fill counselor:

```php
// In ScheduleForm.php
Select::make('schedulable_id')
    ->label('Counselor')
    ->required()
    ->searchable()
    ->preload()
    ->default(function () {
        $user = auth()->user();
        
        // Auto-select counselor if user has counselor role
        if ($user->hasAnyRole(['drug_alcohol', 'spiritual', 'education_outreach', 'outreach'])) {
            return $user->id;
        }
        
        return null;
    })
    ->disabled(function () {
        // Disable if user is a counselor (they can only book for themselves)
        return auth()->user()->hasAnyRole(['drug_alcohol', 'spiritual', 'education_outreach']);
    }),
```

---

## Creating a Seeder

Create `database/seeders/SchedulePermissionsSeeder.php`:

```bash
php artisan make:seeder SchedulePermissionsSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SchedulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Schedule permissions are already created by shield:se
        
        // Define role permissions
        $rolePermissions = [
            'drug_alcohol' => [
                'view_any_schedule',
                'view_schedule',
                'create_schedule',
                'update_schedule',
                'delete_schedule',
            ],
            'spiritual' => [
                'view_any_schedule',
                'view_schedule',
                'create_schedule',
                'update_schedule',
                'delete_schedule',
            ],
            'education_outreach' => [
                'view_any_schedule',
                'view_schedule',
                'create_schedule',
                'update_schedule',
                'delete_schedule',
            ],
            'management' => [
                'view_any_schedule',
                'view_schedule',
                'create_schedule',
                'update_schedule',
                'delete_schedule',
                'lock_schedule',
                'unlock_schedule',
            ],
            'admin' => Permission::all()->pluck('name')->toArray(),
            'frontline' => [
                'view_any_schedule',
                'view_schedule',
            ],
            'assessment' => [
                'view_any_schedule',
                'view_schedule',
            ],
            'aftercare' => [
                'view_any_schedule',
                'view_schedule',
            ],
            'safeguarding' => [
                'view_any_schedule',
                'view_schedule',
            ],
        ];
        
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
```

Run it:
```bash
php artisan db:seed --class=SchedulePermissionsSeeder
```

---

## Verification

After setup, verify:

```php
// In tinker
$user = User::first();
$user->assignRole('drug_alcohol');

// Check permissions
$user->can('create_schedule'); // Should return true

// Check counselor type mapping
$counselorType = match(true) {
    $user->hasRole('drug_alcohol') => CounselorType::DRUG,
    $user->hasRole('spiritual') => CounselorType::SPIRITUAL,
    $user->hasRole('education_outreach') => CounselorType::EDUCATION,
    default => null,
};
```

---

## Next Steps

1. Create availability for each counselor type
2. Test booking appointments as a manager
3. Test lock/unlock functionality
4. Verify notifications are sending
5. Set up cron job for reminder command:
   ```bash
   # Add to crontab
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

---

## Troubleshooting

**Shield permissions not showing?**
```bash
php artisan cache:clear
php artisan config:clear
php artisan shield:install
```

**Roles not working?**
```bash
php artisan cache:clear
# Check role is assigned
$user->hasRole('drug_alcohol'); // Should return true
```

**Can't see Schedule resource in Filament?**
- Check user has permission: `view_any_schedule`
- Clear cache: `php artisan filament:clear-cached-components`