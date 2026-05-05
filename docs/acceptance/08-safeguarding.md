# Safeguarding & Consent Acceptance Criteria

**Module:** Safeguarding Flags, Alerts & Consent Tracking
**AC IDs:** SG-001 to SG-007
**Requirements ref:** §11 — Safeguarding, Consent & Compliance

---

#### SG-001: Create a safeguarding flag [Critical]
**Given** a safeguarding lead (SG) user
**When** they create a safeguarding flag on a service user or enquiry
**Then** the flag is stored with severity, details, and status, and is visible only to safeguarding leads and admins

**QA:** SafeguardingFlag record created with `service_user_id`, `enquiry_id` (optional), `severity`, `details`, `status = open`, `created_by_id`; only visible to SG and AD via global scope or policy.

---

#### SG-002: Safeguarding flag visibility restriction [Critical]
**Given** a safeguarding flag on a service user
**When** a non-safeguarding user (FL, AS, CO, AC, FR, MG) views the service user record
**Then** safeguarding flags are not visible

**QA:** SafeguardingFlag model has a global scope filtering for SG/AD roles; Filament relation manager hidden for non-SG/AD; direct API access returns empty result for non-SG/AD.

---

#### SG-003: Resolve a safeguarding flag [Important]
**Given** an open safeguarding flag
**When** a safeguarding lead resolves it with notes
**Then** the flag status changes to "resolved" with resolved_at timestamp and resolving user

**QA:** Status transition from open to resolved; `resolved_at` and `resolved_by` (optional) recorded; audit log entry created; resolved flags visible but clearly marked.

---

#### SG-004: Safeguarding flag on enquiry [Critical]
**Given** a frontline worker creating an enquiry
**When** they tick the safeguarding flag checkbox
**Then** a safeguarding flag is created and an alert is sent to safeguarding leads

**QA:** `enquiry.safeguarding_flags = true` triggers flag creation; notification sent to SG role users; flag linked to both the enquiry and the associated person.

---

#### SG-005: Restricted notes visibility (cross-reference AC-NT-002/003) [Critical]
*See [03-notes.md](03-notes.md) — this is the same requirement documented there.*

**QA:** Verified under NT-002 and NT-003.

---

#### SG-006: Consent tracking on service user (cross-reference AC-SU-006) [Critical]
*See [02-service-users.md](02-service-users.md) — consent fields documented there.*

**QA:** Verified under SU-006.

---

#### SG-007: Consent field visibility [Important]
**Given** a service user profile with consent fields
**When** viewing the profile
**Then** consent fields (data storage, referrals, communications) are prominently displayed with clear yes/no indicators

**QA:** Filament form shows consent fields as toggles/checkboxes with clear labels; consent status visible in service user list view or summary; changes to consent are audit-logged.