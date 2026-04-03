# Simplified Roles Implementation Summary

## Changes Made

### 1. Migration: Added `counselor_types` to Users Table

**File**: `database/migrations/2026_04_03_182724_add_counselor_types_to_users_table.php`

Added a JSON column to store counselor specialties:
```php
$table->json('counselor_types')->nullable();
```

### 2. User Model Updates

**File**: `app/Models/User.php`

Added methods to manage counselor specialties:

```php
// Casts
'counselor_types' => 'array'

// Methods
public function hasSpecialty(CounselorType $type): bool
public function addSpecialty(CounselorType $type): void
public function removeSpecialty(CounselorType $type): void
```

### 3. Simplified Roles

**File**: `database/seeders/SimplifiedRolePermissionSeeder.php`

Created seeder for new role structure:

| Role | Can View | Can Create | Can Edit | Can Lock/Unlock |
|------|----------|------------|----------|-----------------|
| frontline | ✓ | ✗ | ✗ | ✗ |
| assessment | ✓ | ✗ | ✗ | ✗ |
| **counselor** | ✓ | ✓ | ✓ | ✗ |
| aftercare | ✓ | ✗ | ✗ | ✗ |
| safeguarding | ✓ | ✗ | ✗ | ✗ |
| fundraising | ✓ | ✗ | ✗ | ✗ |
| management | ✓ | ✓ | ✓ | ✓ |
| admin | ✓ | ✓ | ✓ | ✓ |

### 4. Migration Command

**File**: `app/Console/Commands/SyncCounselorSpecialties.php`

Command to migrate old role structure to new structure:

```bash
php artisan counselors:sync-specialties
```

This command:
- Maps old roles (`drug_alcohol`, `spiritual`, `education_outreach`, `outreach`) to counselor types
- Adds specialties to `user.counselor_types`
- Assigns `counselor` role
- Removes old specialty roles

### 5. Policy Updates

**File**: `app/Policies/SchedulePolicy.php`

Updated to use simplified `counselor` role:
- Counselors can create/edit/delete their own schedules
- Managers can book appointments and lock/unlock
- Admins have full access

---

## Setup Instructions

### Step 1: Run Migrations

```bash
php artisan migrate
```

### Step 2: Seed Simplified Roles

```bash
php artisan db:seed --class=SimplifiedRolePermissionSeeder
```

### Step 3: Migrate Existing Users (Optional)

If you have existing users with old specialty roles:

```bash
php artisan counselors:sync-specialties
```

This will:
- Convert `drug_alcohol` role → `counselor` role + `counselor_types = ["drug"]`
- Convert `spiritual` role → `counselor` role + `counselor_types = ["spiritual"]`
- Convert `education_outreach` role → `counselor` role + `counselor_types = ["education"]`

### Step 4: Assign Roles to Users

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Enums\CounselorType;

// Create a new counselor with multiple specialties
$counselor = User::factory()->create(['email' => 'john@example.com']);
$counselor->assignRole('counselor');
$counselor->addSpecialty(CounselorType::DRUG);
$counselor->addSpecialty(CounselorType::SPIRITUAL);

// Create a manager
$manager = User::factory()->create(['email' => 'manager@example.com']);
$manager->assignRole('management');

// Verify specialties
$counselor->counselor_types; // ["drug", "spiritual"]
$counselor->hasSpecialty(CounselorType::DRUG); // true
```

### Step 5: Update Filament User Creation

When creating new counselor users in Filament, you can now:

```php
// In User creation form
Select::make('counselor_types')
    ->label('Counselor Specialties')
    ->multiple()
    ->options(CounselorType::class)
    ->visible(fn ($get) => $get('role') === 'counselor'),
```

---

## Usage Examples

### Creating Availability for Multiple Specialties

```php
use App\Models\User;
use App\Enums\CounselorType;
use Zap\Facades\Zap;

$counselor = User::find(1);

// Add specialties
$counselor->addSpecialty(CounselorType::DRUG);
$counselor->addSpecialty(CounselorType::SPIRITUAL);

// Create Drug Counseling schedule
Zap::for($counselor)
    ->named('Drug Counseling Hours')
    ->availability()
    ->weekly(['monday', 'wednesday'])
    ->addPeriod('09:00', '12:00')
    ->withMetadata([
        'counselor_type' => CounselorType::DRUG->value,
        'team_id' => 1,
        'capacity' => 1,
    ])
    ->save();

// Create Spiritual Counseling schedule
Zap::for($counselor)
    ->named('Spiritual Counseling Hours')
    ->availability()
    ->weekly(['tuesday', 'thursday'])
    ->addPeriod('14:00', '17:00')
    ->withMetadata([
        'counselor_type' => CounselorType::SPIRITUAL->value,
        'team_id' => 1,
        'capacity' => 1,
    ])
    ->save();
```

### Filtering Schedules by Specialty

```php
// Get all drug counseling schedules
$drugSchedules = Schedule::query()
    ->ofType(ScheduleTypes::AVAILABILITY)
    ->where('metadata->counselor_type', CounselorType::DRUG->value)
    ->get();

// Get schedules for a specific counselor's specialties
$counselor = User::find(1);
$schedules = Schedule::query()
    ->where('schedulable_id', $counselor->id)
    ->whereIn('metadata->counselor_type', $counselor->counselor_types)
    ->get();
```

### Checking User Capabilities

```php
// Check if user can create schedules
if (auth()->user()->can('create_schedule')) {
    // User can create availability
}

// Check if user has a specific specialty
if (auth()->user()->hasSpecialty(CounselorType::DRUG)) {
    // Can create drug counseling schedule
}
```

---

## Benefits of This Approach

### ✅ Flexibility
- Counselors can have **multiple specialties** without role changes
- Easy to add/remove specialties dynamically
- No need to create new roles for each specialty

### ✅ Simpler Role Management
- 8 roles instead of 11+
- Clear separation between role (what you can do) and specialty (what you know)
- Easier permission management

### ✅ Realistic
- Matches real-world counseling scenarios
- Supports cross-trained counselors
- Scalable for future specialties

### ✅ Better UX
- Counselor sees all their schedules in one place
- Filter by specialty in Filament admin
- Easy reporting by specialty

---

## Testing

```php
// test a counselor with multiple specialties
$counselor = User::factory()->create();
$counselor->assignRole('counselor');
$counselor->addSpecialty(CounselorType::DRUG);
$counselor->addSpecialty(CounselorType::SPIRITUAL);

expect($counselor->counselor_types)->toBe(['drug', 'spiritual']);
expect($counselor->hasSpecialty(CounselorType::DRUG))->toBeTrue();
expect($counselor->can('create_schedule'))->toBeTrue();

// Test manager permissions
$manager = User::factory()->create();
$manager->assignRole('management');

expect($manager->can('lock_schedule'))->toBeTrue();
expect($manager->can('create_schedule'))->toBeTrue();
```

---

## Rollback Plan

If needed, you can rollback the changes:

```bash
php artisan migrate:rollback --step=1
```

And restore old roles from backup.

---

## Next Steps

1. ✅ Run migrations
2. ✅ Seed new roles
3. ✅ Update Filament forms
4. ✅ Update policies
5. ⏳ Update existing user data
6. ⏳ Test the system
7. ⏳ Document for end users

---

## Questions?

- **Can a counselor have no specialties?** Yes, they just won't be able to create availability until they have at least one.
- **Can management/admin create schedules?** Yes, with full permissions including lock/unlock.
- **What about team-based permissions?** Already handled via `metadata.team_id` in schedules.
- **Can a counselor edit another counselor's schedule?** No, policy only allows editing own schedules.