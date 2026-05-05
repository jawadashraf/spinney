# Audit Trail Acceptance Criteria

**Module:** Full Audit Trail
**AC IDs:** AT-001 to AT-004
**Requirements ref:** §11 — Safeguarding (audit trail requirement)

---

#### AT-001: Automatic logging of model changes [Critical]
**Given** any authenticated user
**When** they create, update, or delete an Enquiry, People, ServiceUserProfile, Note, Company, Task, or Schedule
**Then** an activity log entry is created with the user, action, subject, and changed properties

**QA:** `activity_log` table has entries for each CRUD action; entry includes `causer_id`, `subject_type`, `subject_id`, `description`, `properties` (old/new values), `created_at`; Spatie ActivityLog package configured for all relevant models.

---

#### AT-002: Access logging for restricted notes [Critical]
**Given** a safeguarding lead viewing a restricted note
**When** they access the note
**Then** the access is logged with timestamp and user identity

**QA:** Activity log entry with `event = 'viewed'` for restricted note access; includes `causer_id` and `subject_id`.

---

#### AT-003: Audit log access permissions [Critical]
**Given** a manager (MG) or admin (AD) user
**When** they navigate to the audit log
**Then** they can view all activity log entries filtered by user, model, action, and date range

**QA:** Filament ActivityLog resource accessible to MG/AD roles only; filterable by `causer_id`, `subject_type`, `event`, `created_at`; entries cannot be edited or deleted by any role except AD.

---

#### AT-004: Audit log is read-only [Critical]
**Given** any user
**When** they view the audit log
**Then** they cannot create, update, or delete log entries manually

**QA:** No create/update/delete actions in ActivityLog resource; direct API requests return 403; only system-generated entries exist.