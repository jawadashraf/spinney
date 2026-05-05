# Cross-Cutting Acceptance Criteria

**Module:** Authentication, Tenancy, RBAC & Data Isolation
**AC IDs:** CC-001 to CC-007
**Requirements ref:** §2, §3, §10

---

#### CC-001: User login and session [Critical]
**Given** a user with valid credentials
**When** they log in via the Filament admin panel
**Then** they are authenticated and redirected to their role-appropriate dashboard

**QA:** Fortify auth routes respond correctly; invalid credentials show error; session persists across requests; 2FA enforced for roles that require it.

---

#### CC-002: Unauthorised access is denied [Critical]
**Given** an unauthenticated user
**When** they attempt to access any protected route
**Then** they are redirected to the login page

**QA:** All Filament resource routes return 403 for unauthenticated users; no data leaks in redirect responses.

---

#### CC-003: Team tenancy data isolation [Critical]
**Given** two separate teams (tenants) with their own records
**When** a user in Team A views any list or record
**Then** they see only Team A's data — no records from Team B are visible

**QA:** Global scope on all tenant-scoped models filters by `team_id`; direct URL access to `/admin/enquiries/{id}` where `enquiry.team_id != user.team_id` returns 403 or 404.

---

#### CC-004: Role-based permission enforcement [Critical]
**Given** a user assigned role `frontline`
**When** they attempt to delete an enquiry
**Then** the action is denied (no delete permission for FL role)

**QA:** Spatie Shield permissions are checked via policies; Filament actions hidden/disabled for unauthorised roles; `EnquiryPolicy::delete()` returns false for FL role.

---

#### CC-005: Audit trail on all create/update/delete [Critical]
**Given** any authenticated user performing a data-changing action
**When** they create, update, or delete a record
**Then** an activity log entry is created with user_id, action, subject_type, subject_id, properties, and timestamp

**QA:** `activity_log` table receives entries for all CRUD operations on Enquiry, People, ServiceUserProfile, Note, Company, Task, Schedule, Donation; properties contain changed attributes.

---

#### CC-006: Soft delete and restore [Important]
**Given** a record with soft-delete support (People, Note, Company, Task, ServiceUserProfile)
**When** a user with delete permission deletes the record
**Then** the record is soft-deleted (`deleted_at` set) and restorable by users with restore permission

**QA:** `deleted_at` column is set; record no longer appears in default lists; `restore` action available to MG/AD roles; `forceDelete` available only to AD.

---

#### CC-007: Record locking prevents accidental edits [Important]
**Given** a service user record where `is_locked = true`
**When** a non-management, non-admin user attempts to edit the record
**Then** the edit action is denied

**QA:** `PeoplePolicy::update()` returns false when `is_locked = true` for FL/AS/CO/AC roles; MG/AD can still edit.