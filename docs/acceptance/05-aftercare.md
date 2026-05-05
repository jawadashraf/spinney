# Telephone Check-In & Aftercare Acceptance Criteria

**Module:** Telephone Check-In & Aftercare
**AC IDs:** AF-001 to AF-006
**Requirements ref:** §8 — Telephone Check-In & Aftercare

---

#### AF-001: Log a check-in call [Critical]
**Given** an aftercare worker (AC)
**When** they log a telephone check-in for a service user
**Then** a task record is created with type = Follow-up Call, linked to the service user, with outcome and next steps

**QA:** Task created with `type = follow_up_call`, `taskable_type = People`, `taskable_id = service_user_id`; includes `due_date`, assigned to AC user; visible in service user's case file under related tasks.

---

#### AF-002: Follow-up task auto-generation — Active users [Important]
**Given** a service user with `engagement_status = Active`
**When** a check-in call is logged
**Then** a follow-up task is auto-generated with due date = 1 week or 2 weeks (configurable) from the call date

**QA:** Follow-up task created with `type = follow_up_call`, due date set per configured interval; task assigned to the aftercare team/worker; creation logged in audit trail.

---

#### AF-003: Follow-up task auto-generation — Post-care users [Important]
**Given** a service user with `engagement_status = Inactive` (post-care, sober < 2 years)
**When** a check-in call is logged
**Then** a monthly follow-up task is auto-generated

**QA:** Follow-up task created with monthly interval for inactive/post-care users; system differentiates between Active (weekly/bi-weekly) and Inactive (monthly) schedules.

---

#### AF-004: Due/overdue task alerts [Important]
**Given** follow-up tasks with approaching or past due dates
**When** a task is due today or overdue
**Then** the task appears in an alert/dashboard widget for aftercare workers and managers

**QA:** Task list filtered by `due_date <= today` and `status != completed`; Filament widget or notification shows due/overdue tasks; MG can view all overdue tasks across teams.

---

#### AF-005: Engagement history on service user record [Critical]
**Given** a service user with logged check-ins
**When** viewing the service user case file
**Then** all check-in calls, follow-ups, outcomes, and next steps are visible in chronological order

**QA:** Task relation manager on ServiceUserResource shows related tasks with type `follow_up_call`; tasks ordered by `created_at` descending; each task shows outcome, next steps, and assigned worker.

---

#### AF-006: Aftercare permissions by role [Critical]

| Role | Create Check-in | View Check-ins | Update Follow-ups |
|------|----------------|----------------|-------------------|
| AC | Yes | Yes (assigned) | Yes (own) |
| SG | No | Yes (all) | No |
| MG | No | Yes (all) | Yes |
| AD | Yes | Yes (all) | Yes |

**QA:** TaskPolicy enforces per-role check-in and follow-up permissions; AC can only create tasks with `type = follow_up_call` for assigned service users.