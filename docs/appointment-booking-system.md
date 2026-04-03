# Appointment Booking System Documentation

## Overview

This appointment booking system uses **laravel-zap** for flexible schedule management. It supports counselors managing their availability, managers booking appointments for service users, and attendance tracking for group sessions.

---

## Architecture

### Technology Stack
- **laravel-zap**: Schedule management package for availabilities, appointments, and blocked times
- **Filament v5**: Admin panel for managing schedules
- **Spatie Shield**: Role-based permissions
- **Laravel Notifications**: Email and database notifications

### Database Schema
- `schedules` - Main table storing all schedule types (availability, appointment, blocked)
- `schedule_periods` - Time periods within schedules
- `metadata` JSON field - Stores custom data (counselor_type, team_id, service_user_id, etc.)

---

## Roles & Permissions

### Existing Roles

| Role | Description | Schedule Permissions |
|------|-------------|---------------------|
| `drug_alcohol` | Drug & Alcohol Counselor | Create/manage drug counseling availability |
| `spiritual` | Spiritual Counselor | Create/manage spiritual counseling availability |
| `education_outreach` | Education & Outreach Worker | Create/manage education/outreach schedules |
| `management` | Manager | Book appointments, lock/unlock, manage all |
| `admin` | Administrator | Full system access |
| `frontline` | Frontline Staff | View schedules (read-only) |
| `assessment` | Assessment Team | View schedules |
| `aftercare` | Aftercare Team | View schedules |
| `safeguarding` | Safeguarding Team | View schedules |
| `fundraising` | Fundraising Team | No access |

### Permission Matrix

```
                    View    Create    Update    Delete    Lock/Unlock
drug_alcohol         ✓        ✓         ✓         ✓          ✗
spiritual            ✓        ✓         ✓         ✓          ✗
education_outreach   ✓        ✓         ✓         ✓          ✗
management           ✓        ✓         ✓         ✓          ✓
admin                ✓        ✓         ✓         ✓          ✓
frontline            ✓        ✗         ✗         ✗          ✗
assessment           ✓        ✗         ✗         ✗          ✗
aftercare            ✓        ✗         ✗         ✗          ✗
safeguarding         ✓        ✗         ✗         ✗          ✗
```

---

## User Guide

### For Counselors

#### Creating Availability

**Step 1: Navigate to Schedules**
- Go to Admin Panel → Appointments → Schedules
- Click "Create" button

**Step 2: Fill in Availability Details**
```
Schedule Type: Availability
Counselor: [Your name - auto-selected]
Name: "Office Hours - Drug Counseling"
Description: (Optional)
Start Date: 2025-04-03
Is Recurring: Yes
Frequency: Weekly
Days of Week: Monday, Wednesday, Friday
Start Time: 09:00
End Time: 12:00
Slot Duration: 60 minutes
Capacity: 1 (for individual sessions)
Counselor Type: Drug
```

**Step 3: Save**
- Click "Create"
- Your availability is now visible to managers

#### Viewing Your Availability
- Filter by `schedule_type` = "Availability"
- Filter by `schedulable_id` = [Your ID]

---

### For Managers

#### Booking Appointments

**Step 1: Find Available Slots**
- Go to Schedules → Filter by `schedule_type` = "Availability"
- Or use Zap API: `$counselor->getBookableSlots('2025-04-03', 60, 0)`

**Step 2: Create Appointment**
```
Schedule Type: Appointment
Service User: [Select from People]
Start Date: 2025-04-03
Is Recurring: No
Start Time: 10:00
End Time: 11:00
Manager: [Your ID]
```

**Step 3: Lock Availability**
- After booking, lock the availability to prevent counselor changes
- Filter by schedule → Click "Lock" action

#### Managing Locks
- **Lock**: Prevents counselor from editing availability
- **Unlock**: Allows counselor to edit again
- Only managers and admins can lock/unlock

---

### For Service Users

Service users (People with `is_service_user = true`) don't have direct access.
They receive notifications when:
- Appointment is booked for them
- Appointment is cancelled
- Reminder 24 hours before appointment

---

## Schedule Types

### Availability Schedule

**Purpose**: Defines when a counselor is available for booking

**Metadata Fields**:
```json
{
  "team_id": 1,
  "counselor_type": "drug",
  "slot_duration_minutes": 60,
  "capacity": 1,
  "is_locked": false
}
```

**Example Creation**:
```php
use Zap\Facades\Zap;
use App\Enums\CounselorType;

Zap::for($counselor)
    ->named('Office Hours - Drug Counseling')
    ->availability()
    ->forYear(2025)
    ->addPeriod('09:00', '12:00')
    ->addPeriod('14:00', '17:00')
    ->weekly(['monday', 'wednesday', 'friday'])
    ->withMetadata([
        'team_id' => auth()->user()->current_team_id,
        'counselor_type' => CounselorType::DRUG->value,
        'slot_duration_minutes' => 60,
        'capacity' => 1,
        'is_locked' => false,
    ])
    ->save();
```

---

### Appointment Schedule

**Purpose**: A booked session with a service user

**Metadata Fields**:
```json
{
  "team_id": 1,
  "service_user_id": 123,
  "service_user_name": "John Doe",
  "manager_id": 456,
  "session_type": "individual",
  "cancellation_reason": null,
  "cancelled_at": null,
  "cancelled_by": null
}
```

**Example Creation**:
```php
Zap::for($counselor)
    ->named('Session with John Doe')
    ->appointment()
    ->from('2025-04-03')
    ->addPeriod('10:00', '11:00')
    ->withMetadata([
        'team_id' => auth()->user()->current_team_id,
        'service_user_id' => $serviceUser->id,
        'service_user_name' => $serviceUser->name,
        'manager_id' => auth()->id(),
        'session_type' => 'individual',
    ])
    ->save();
```

---

### Blocked Schedule

**Purpose**: Time periods when counselor is unavailable (holidays, training, etc.)

**Metadata Fields**:
```json
{
  "team_id": 1,
  "reason": "Annual Leave"
}
```

**Example Creation**:
```php
Zap::for($counselor)
    ->named('Annual Leave')
    ->blocked()
    ->from('2025-04-10')
    ->to('2025-04-15')
    ->addPeriod('00:00', '23:59')
    ->withMetadata([
        'team_id' => auth()->user()->current_team_id,
        'reason' => 'Annual Leave',
    ])
    ->save();
```

---

## Workflow

### Booking Workflow

```
1. Counselor creates availability
   └─> Schedule type: availability
   └─> Status: active

2. Manager views available slots
   └─> Filter by schedule_type = availability
   └─> Check capacity and slot duration

3. Manager books appointment
   └─> Schedule type: appointment
   └─> Check for conflicts using Zap::findConflicts()

4. System sends notifications
   └─> Email + database notification to counselor
   └─> Email + database notification to service user

5. Manager locks availability
   └─> Update availability metadata: is_locked = true

6. Appointment reminder (24 hours before)
   └─> Console command: appointments:send-reminders
   └─> Scheduled daily at 09:00
```

### Cancellation Workflow

```
1. Manager cancels appointment
   └─> Must be at least 24 hours before appointment
   └─> Reason recorded in metadata

2. System updates appointment
   └─> Set is_active = false
   └─> Store cancellation_reason, cancelled_at, cancelled_by

3. Notifications sent
   └─> Email + database notification to counselor
   └─> Email + database notification to service user

4. Availability remains locked
   └─> Manager can unlock if needed
```

### Attendance Tracking

Attendance is stored in the appointment metadata:
```json
{
  "attendances": [
    {
      "service_user_id": 123,
      "service_user_name": "John Doe",
      "status": "attended",
      "marked_by": 456,
      "marked_at": "2025-04-03 10:05:00"
    }
  ]
}
```

---

## API Reference

### Get Bookable Slots

```php
// Get all available slots for a specific date
$slots = $counselor->getBookableSlots('2025-04-03', 60, 0);

// Parameters:
// - date: The date to check
// - duration: Slot duration in minutes (30-60)
// - buffer: Buffer time between slots in minutes (default: 0)

// Returns: Collection of available time slots
```

### Check Availability

```php
// Check if a specific time slot is available
$available = $counselor->isBookableAt('2025-04-03', 60);

// Check if a specific time range is available
$available = $counselor->isBookableAtTime(
    '2025-04-03',  // date
    '10:00',       // start time
    '11:00'        // end time
);
```

### Find Conflicts

```php
use Zap\Facades\Zap;

// Check for conflicts before creating appointment
$conflicts = Zap::hasConflicts($schedule);

// Get detailed conflict information
$conflicts = Zap::findConflicts($schedule);
```

---

## Console Commands

### Send Appointment Reminders

```bash
php artisan appointments:send-reminders
```

**Schedule** (in `routes/console.php`):
```php
Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();
```

**What it does**:
- Finds all appointments scheduled for tomorrow
- Sends reminder notifications to counselor and service user
- Runs daily at 09:00 AM

---

## Filtering & Searching

### In Filament Admin Panel

**Filter by Schedule Type**:
- Availability
- Appointment
- Blocked

**Filter by Counselor**:
- Dropdown with all users who have counselor roles

**Filter by Counselor Type**:
- Spiritual
- Drug
- Education
- Outreach

**Filter by Date Range**:
- Start date filter
- End date filter

### Programmatic Queries

```php
use App\Models\Schedule;
use Zap\Enums\ScheduleTypes;

// Get all availabilities
$availabilities = Schedule::ofType(ScheduleTypes::AVAILABILITY)
    ->where('is_active', true)
    ->get();

// Get appointments for a specific date
$appointments = Schedule::ofType(ScheduleTypes::APPOINTMENT)
    ->where('start_date', '2025-04-03')
    ->get();

// Get counselor's schedules
$counselorSchedules = $counselor->schedules()
    ->where('start_date', '>=', now())
    ->get();
```

---

## Notifications

### Appointment Booked

**Triggers**: When manager creates an appointment

**Recipients**:
- Counselor (email + database)
- Service User (email + database)

**Content**:
- Date and time
- Counselor name
- Service user name
- Duration

---

### Appointment Cancelled

**Triggers**: When manager cancels an appointment

**Recipients**:
- Counselor (email + database)
- Service User (email + database)

**Content**:
- Date and time
- Cancellation reason
- Cancelled by

---

### Appointment Reminder

**Triggers**: 24 hours before appointment (via console command)

**Recipients**:
- Counselor (email + database)
- Service User (email + database)

**Content**:
- Appointment details
- Reminder message

---

## Troubleshooting

### Common Issues

**Issue**: "Cannot create appointment, slot not available"
**Solution**: Check for conflicts using `Zap::findConflicts()` or ensure availability exists for the time slot

**Issue**: "Counselor cannot edit locked availability"
**Solution**: Manager must unlock the availability first via "Unlock" action

**Issue**: "Notifications not sending"
**Solution**: 
- Check queue worker is running: `php artisan queue:work`
- Verify mail configuration in `.env`
- Check `notifications` table for failed notifications

**Issue**: "Service user not receiving notifications"
**Solution**: Ensure `People` model has a related `User` with valid email

---

## Best Practices

### For Counselors

1. **Create availability in advance**
   - At least 1-2 weeks ahead
   - Use recurring schedules for regular hours

2. **Set appropriate capacity**
   - 1 for individual sessions
   - 2+ for group sessions/classes

3. **Add descriptions**
   - Include session type
   - List any requirements or notes

4. **Block unavailable times**
   - Vacation days
   - Training sessions
   - Other commitments

### For Managers

1. **Lock availability after booking**
   - Prevents accidental changes
   - Ensures appointment integrity

2. **Check for conflicts**
   - Use available slots API
   - Verify counselor availability

3. **Provide complete information**
   - Service user details
   - Session purpose
   - Any special requirements

4. **Cancel with notice**
   - Minimum 24 hours required
   - Provide clear reason

### Common Patterns

#### Individual Counseling Sessions
```php
// Capacity: 1
// Duration: 30-60 minutes
// Type: individual
```

#### Group Sessions/Classes
```php
// Capacity: 5-20
// Duration: 60-120 minutes
// Type: group
// Consider: Outreach counselor type
```

#### Recurring Weekly Availability
```php
->forYear(2025)
->weekly(['monday', 'wednesday', 'friday'])
```

#### Single-Day Availability
```php
->from('2025-04-03')
->to('2025-04-03')
```

---

## Security Considerations

### Authorization

- All schedule operations check user roles
- Counselors can only manage their own schedules
- Managers have full access within their team
- Admins have global access

### Data Protection

- Service user information stored in metadata (JSON field)
- Only authorized personnel can view PII
- Cancellation reasons are optional but recommended

### Team Scoping

- All schedules scopes to team via `metadata.team_id`
- Cross-team access restricted to admins
- Filament resources filtered by current team

---

## Testing

### Unit Tests

```bash
php artisan test --filter=ScheduleTest
```

### Feature Tests

```php
// Test availability creation
it('allows counselor to create availability', function () {
    $counselor = User::factory()->create()->assignRole('drug_alcohol');
    
    livewire(CreateSchedule::class)
        ->fillForm([
            'schedule_type' => 'availability',
            'name' => 'Office Hours',
            // ... other fields
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});
```

---

## Support

For issues or questions:
1. Check this documentation
2. Review laravel-zap documentation: https://laravel-zap.com
3. Check application logs: `storage/logs/laravel.log`
4. Contact system administrator

---

## Version History

- **v1.0.0** (2025-04-03): Initial release with laravel-zap integration

---

## License

This system is proprietary software. All rights reserved.