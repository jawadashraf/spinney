# Appointments & Shared Diaries Acceptance Criteria

**Module:** Appointments & Diary Management
**AC IDs:** AP-001 to AP-007
**Requirements ref:** §6 — Appointments & Diary Management

---

#### AP-001: Create an appointment [Critical]
**Given** a frontline worker (FL) or admin (AD)
**When** they book an appointment selecting service user, counselor, type, date, start/end time
**Then** the appointment is created in the Schedule system with status "scheduled"

**QA:** Schedule created with `schedule_type = appointment`; `schedulable_type = User` (counselor); metadata includes `service_user_id`, `counselor_type`, `payment_type`; status defaults to `scheduled` (AppointmentStatus enum).

---

#### AP-002: Appointment type classification [Critical]
**Given** a user creating an appointment
**When** they select the appointment type
**Then** the type must be one of: Assessment, Drug & Alcohol, Spiritual, Education & Outreach, Aftercare

**QA:** Metadata field `counselor_type` stores a valid `CounselorType` enum value; appointment visible to counselors matching that specialty.

---

#### AP-003: Counselor availability validation [Important]
**Given** a counselor with defined availability schedules
**When** a user books an appointment outside the counselor's available hours
**Then** the system warns or prevents the booking

**QA:** Schedule system checks `schedule_type = availability` for the counselor on the selected date; overlapping or out-of-availability appointments trigger validation error or warning.

---

#### AP-004: Email confirmation on booking [Important]
**Given** an appointment is successfully created
**When** the booking is saved
**Then** an email confirmation is sent to the service user (if email provided) and/or the assigned counselor

**QA:** `SendAppointmentReminders` or similar notification fires; Mailable/Notification class sends to relevant recipients; email includes date, time, counselor name, location, and cancellation link.

---

#### AP-005: Appointment status transitions [Important]
**Given** an appointment with status "scheduled"
**When** status changes occur
**Then** valid transitions are: scheduled → confirmed → completed, scheduled → cancelled, confirmed → cancelled

**QA:** `AppointmentStatus` enum defines valid states; Filament action buttons for status transitions; audit log records each status change.

---

#### AP-006: Appointment permissions by role [Critical]

| Role | Create | View | Update | Cancel | Delete |
|------|--------|------|--------|--------|--------|
| FL | Yes | Yes | No | No | No |
| AS | No | Yes (assigned) | No | No | No |
| CO | No | Yes (own) | Yes (own) | Yes (own) | No |
| SG | No | Yes (all) | No | No | No |
| MG | Yes | Yes (all) | Yes | Yes | Yes |
| AD | Yes | Yes (all) | Yes | Yes | Yes |

**QA:** SchedulePolicy enforces per-role permissions; "own" means `schedulable_id = auth()->id()`; "assigned" means service user is in user's assigned team.

---

#### AP-007: Shared diary view [Desirable]
**Given** a user with view appointment permissions
**When** they access the diary/calendar view
**Then** they see all appointments they are authorised to view in a calendar or list format

**QA:** Filament calendar/list widget shows appointments scoped to user's access level; FL sees all bookings; CO sees own availability + booked appointments; MG sees all.