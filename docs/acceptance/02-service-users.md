# Service Users & Case File Acceptance Criteria

**Module:** Service User Registration & Case Management
**AC IDs:** SU-001 to SU-007
**Requirements ref:** §5 — Service User Registration & Case Management

---

#### SU-001: Create service user from enquiry conversion [Critical]
**Given** an open enquiry (see EQ-005)
**When** the conversion action completes
**Then** a People record is created with `is_service_user = true`, and a ServiceUserProfile is created with all enquiry data transferred

**QA:** People record has `is_service_user = true`; ServiceUserProfile linked with `person_id`; service user accessible via ServiceUserResource; default `engagement_status = pending`.

---

#### SU-002: Create service user directly [Critical]
**Given** a user with `Create:ServiceUser` permission
**When** they create a new service user with required fields (name, date of birth, target service team)
**Then** a People record with `is_service_user = true` and linked ServiceUserProfile are created

**QA:** Direct creation via ServiceUserResource; `is_service_user` set to `true`; ServiceUserProfile auto-created; required fields validated.

---

#### SU-003: Service user profile fields [Critical]
**Given** an existing service user
**When** viewing their case file
**Then** the following profile sections are visible:
- Personal details (name, DOB, gender, ethnicity, address, postcode, no fixed address)
- Contact information (phone, email)
- Emergency contact (name, number)
- Substance use details (addictions, substances used, frequency, amount, route of use, age first used, overdose history, injection history)
- GP registration (registered with GP, GP name, GP address)
- Engagement (target service team, engagement status, referral type, referral source)
- Consent (data storage, referrals, communications)

**QA:** All fields from `ServiceUserProfile` and `People` models render in the case file view; enum fields display labels not values.

---

#### SU-004: Service team assignment [Critical]
**Given** a service user profile
**When** the target service team is set
**Then** the assignment is one of: Assessment, Drug & Alcohol, Spiritual, Education & Outreach, Aftercare

**QA:** `ServiceTeam` enum values are the only valid options; assignment determines which counselor types can access the record (based on `counselor_types` matching).

---

#### SU-005: Engagement status lifecycle [Important]
**Given** a service user with engagement status "Pending"
**When** their engagement changes
**Then** status can transition through: Pending → Active → Inactive → Discharged

**QA:** `EngagementStatus` enum allows all four states; status changes are audit-logged; status transitions visible in case file timeline.

---

#### SU-006: Consent tracking [Critical]
**Given** a service user record
**When** consent fields are set (data storage, referrals, communications)
**Then** each consent field is a boolean that is explicitly captured and visible on the profile

**QA:** `consent_data_storage`, `consent_referrals`, `consent_communications` are boolean fields; default to `false`; must be explicitly set; displayed as checkboxes/toggles in Filament form.

---

#### SU-007: Service user permissions by role [Critical]

| Role | Create | View | Update | Delete |
|------|--------|------|--------|--------|
| FL | No | Yes (limited) | No | No |
| AS | Yes | Yes (assigned) | Yes (assigned) | No |
| CO | Yes | Yes (assigned) | Yes (assigned) | No |
| SG | Yes | Yes (all) | Yes (all) | No |
| MG | No | Yes (all) | No | No |
| AD | Yes | Yes (all) | Yes (all) | Yes |

**QA:** ServiceUserPolicy enforces role-based access; "assigned" means service user's `target_service_team` matches the user's `counselor_types` or department.