# Classes & Group Sessions Acceptance Criteria

**Module:** Classes & Group Sessions
**AC IDs:** CL-001 to CL-020
**Requirements ref:** §6 — Appointments & Diary Management, §13.2 — Specialized Care Roles, §13.5 — Payment Tracking

---

#### CL-001: Create a class [Critical]
**Given** a manager (MG) or admin (AD)
**When** they create a class selecting title, counselor, category, capacity, and venue
**Then** the class is created with status "draft"

**QA:** ClassModel created with `status = draft` (ClassStatus enum); `counselor_type` stored as CounselorType enum; `is_free` defaults to `true`; slug auto-generated from title; team-scoped via `team_id`.

---

#### CL-002: Class category classification [Critical]
**Given** a user creating a class
**When** they select the category
**Then** the category must be one of: Drug & Alcohol, Spiritual, Education, Outreach, Wellbeing

**QA:** `category` column stores a valid `ClassCategory` enum value; Filament `Select` uses `ClassCategory::class` for options; badge colors applied per enum.

---

#### CL-003: Class status lifecycle [Important]
**Given** a class with status "draft"
**When** status changes occur
**Then** valid transitions are: draft → published, published → archived, published → cancelled, draft → cancelled

**QA:** `ClassStatus` enum defines valid states; Filament action buttons for Publish, Archive, Cancel; audit log records each status change; Publish validates that at least one session exists.

---

#### CL-004: Recurring session generation [Important]
**Given** a manager viewing a class's sessions
**When** they use the "Generate Recurring Sessions" bulk action
**Then** they select a date range and days of week, and sessions are created for each matching date

**QA:** `ClassSession` records created for each selected day_of_week between start_date and end_date; deduplication prevents same class_model_id + date + start_time; sessions inherit start_time and end_time from the form; venue_override defaults to null (inherits from class).

---

#### CL-005: Publish and archive a class [Important]
**Given** a manager with 'update_class_model' permission
**When** they publish a draft class
**Then** the class becomes visible on the public booking page

**QA:** Publish action sets `status = published`; class appears in `ClassModel::where('status', 'published')` query used by public browse page; archive action sets `status = archived`; archived classes are hidden from public page but visible in admin.

---

#### CL-006: Class pricing — free vs paid [Critical]
**Given** a user creating or editing a class
**When** they toggle the "is_free" switch
**Then** the price_per_session field is shown or hidden accordingly

**QA:** `is_free` toggle uses `->live()` for reactive visibility; when `is_free = true`, `price_per_session` is set to null and hidden; when `is_free = false`, `price_per_session` is shown and required; free classes skip Stripe checkout entirely in booking flow.

---

#### CL-007: Book a class — full course [Critical]
**Given** a service user or outsider on the public class detail page
**When** they select "Full Course" booking type and confirm
**Then** they are enrolled in all upcoming sessions for that class

**QA:** `ClassBooking` created with `booking_type = full_class`; `ClassAttendance` records created for each future `ClassSession` with `status = booked`; total calculated as `price_per_session × upcoming_sessions_count`; if free class, `status = confirmed` immediately; if paid, `status = pending_payment` and Stripe checkout initiated.

---

#### CL-008: Book a class — drop-in [Critical]
**Given** a service user or outsider on the public class detail page
**When** they select "Drop-in" booking type and choose specific sessions
**Then** they are enrolled only in the selected sessions

**QA:** `ClassBooking` created with `booking_type = drop_in`; `ClassAttendance` records created only for selected `ClassSession` IDs; total calculated as `price_per_session × selected_sessions_count`; session selection checkbox list shows only upcoming sessions with available capacity.

---

#### CL-009: Booking for service user — free [Critical]
**Given** a service user (or staff member on their behalf)
**When** they book a free class
**Then** the booking is confirmed immediately without payment

**QA:** `ClassBooking` created with `payment_type = free`, `payment_status = unpaid`, `status = confirmed`; `confirmed_at` set to now; `ClassBookingConfirmedNotification` sent to person and counselor; no Stripe session created.

---

#### CL-010: Booking for outsider — paid via Stripe [Critical]
**Given** an outsider on the public booking page
**When** they book a paid class
**Then** they are redirected to Stripe Checkout to complete payment

**QA:** `ClassBooking` created with `payment_type = paid`, `payment_status = unpaid`, `status = pending_payment`; `stripe_checkout_session_id` stored on booking; user redirected to Stripe Checkout URL; on successful payment, webhook handler `handleCheckoutCompleted` sets `payment_status = paid` and `status = confirmed`; `ClassBookingConfirmedNotification` sent after confirmation.

---

#### CL-011: Outsider account creation [Important]
**Given** a guest (not logged in) on the booking page
**When** they provide their name, email, and optionally create an account
**Then** a People record (type 'attendee') and optionally a User account are created

**QA:** `Attendee` record created via Parental with `type = 'attendee'` in `people` table; if "Create account" toggle is on, `User` record created with given email and password, linked to People via `user_id`; existing users are matched by email; `AccountCreatedNotification` sent to new users with setup link.

---

#### CL-012: Public class browsing [Important]
**Given** any visitor to the /classes page
**When** they browse available classes
**Then** they see all published classes with title, counselor, category, price, next session date, and remaining capacity

**QA:** Only `ClassModel::where('status', 'published')` are shown; eager-load counselor and upcoming sessions to prevent N+1; cards display category badge via `ClassCategory` enum; price shown as "Free" when `is_free = true`, otherwise formatted as currency; capacity shown as "X spots remaining".

---

#### CL-013: Stripe Checkout integration [Critical]
**Given** a booking that requires payment
**When** the user clicks "Pay & Book"
**Then** a Stripe Checkout Session is created and the user is redirected

**QA:** `StripeCheckoutService::createCheckoutSession()` called with class title, price, and metadata (`class_model_id`, `person_id`, `booking_id`); `success_url` points to `/booking/confirm?session_id={CHECKOUT_SESSION_ID}`; `cancel_url` points to `/booking/cancel`; Stripe session ID stored in `stripe_checkout_session_id` on ClassBooking; booking route rate-limited to 10 requests per 60 seconds per IP.

---

#### CL-014: Stripe webhook handling [Important]
**Given** a Stripe webhook for `checkout.session.completed`
**When** the event is received at POST /stripe/webhook
**Then** the corresponding booking is confirmed

**QA:** `StripeWebhookController` verifies webhook signature using `STRIPE_WEBHOOK_SECRET`; `checkout.session.completed` → `ClassBookingService::confirmBooking()` sets `payment_status = paid`, `status = confirmed`, stores `stripe_payment_intent_id`; idempotent — re-processing same session is safe; `charge.refunded` events logged but do not auto-refund (manual process via Stripe Dashboard + admin marking).

---

#### CL-015: Cancel a class [Important]
**Given** a manager or admin
**When** they cancel a class and optionally choose to cancel all bookings
**Then** the class status is set to "cancelled" and optionally all bookings and sessions are cancelled

**QA:** CancelClass action sets `status = cancelled` on ClassModel; if `cancel_bookings = true`, all active ClassBooking records set to `status = cancelled`, `cancelled_at = now`; all scheduled ClassSession records set to `status = cancelled`; all related ClassAttendance records set to `status = cancelled`; `ClassBookingCancelledNotification` sent for each cancelled booking.

---

#### CL-016: Cancel a booking [Important]
**Given** a manager, admin, or the booking owner
**When** they cancel a booking with a reason
**Then** the booking and all its attendance records are cancelled

**QA:** ClassBooking `status = cancelled`, `cancelled_at = now`, `cancellation_reason` stored; all ClassAttendance records for this booking set to `status = cancelled`; `ClassBookingCancelledNotification` sent to person and counselor; capacity released (available spots increase for affected sessions).

---

#### CL-017: Mark attendance [Critical]
**Given** a counselor or manager viewing attendance for a class session
**When** they mark an attendee's status as "attended" or "no show"
**Then** the attendance record is updated with the new status, marked_by, and marked_at

**QA:** `ClassAttendance` record updated with `status = attended` or `no_show`; `marked_by` set to `auth()->id()`; `marked_at` set to `now()`; counselors can only mark attendance for sessions in their own classes; managers can mark attendance for any class.

---

#### CL-018: Attendance bulk actions [Important]
**Given** a manager or admin on the attendance list page
**When** they select multiple attendance records and choose "Mark as Attended" or "Mark as No Show"
**Then** all selected records are updated in bulk

**QA:** Bulk "Mark Attended" sets `status = attended`, `marked_by = auth()->id()`, `marked_at = now()` for all selected; bulk "Mark No Show" sets `status = no_show` with same metadata; only users with `update_class_attendance` permission can perform bulk actions; success notification shows count.

---

#### CL-019: Class permissions by role [Critical]

| Role | View Classes | Create Class | Edit Class | Publish/Archive | Cancel Class | View Bookings | Create Booking | Cancel Booking | Mark Attendance | Refund |
|------|-------------|-------------|------------|-----------------|-------------|---------------|----------------|----------------|-----------------|--------|
| FL | Yes | — | — | — | — | Yes | Yes | — | View | — |
| AS | Yes | — | — | — | — | — | — | — | — | — |
| CO | Yes (own) | — | — | — | — | Yes (own) | — | — | Yes (own) | — |
| SG | Yes | — | — | — | — | — | — | — | — | — |
| MG | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| AD | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |

**QA:** `ClassModelPolicy`, `ClassBookingPolicy`, `ClassAttendancePolicy` enforce per-role permissions; "own" means `counselor_id = auth()->id()`; Shield permissions seeded via `SimplifiedRolePermissionSeeder`.

---

#### CL-020: Session reminders [Desirable]
**Given** class sessions scheduled for tomorrow
**When** the daily `classes:send-reminders` command runs at 09:00
**Then** reminder notifications are sent to each attendee and the counselor

**QA:** Command finds all `ClassSession` where `date = tomorrow` and `status = scheduled`; for each session, finds all `ClassAttendance` where `status = 'booked'`; sends `ClassSessionReminderNotification` (mail + database) to each person and counselor 24 hours before; sends `ClassAttendanceReminderNotification` (database only) to counselor 1 hour before; command scheduled in `routes/console.php` with `withoutOverlapping()` and `onOneServer()`.