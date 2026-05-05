# Enquiries Acceptance Criteria

**Module:** Enquiry Management
**AC IDs:** EQ-001 to EQ-008
**Requirements ref:** §4 — Enquiry Management

---

#### EQ-001: Create an enquiry with all required fields [Critical]
**Given** a frontline worker (FL) is logged in
**When** they submit a new enquiry with category, caller details, reason for contact, and action taken
**Then** the enquiry is saved with status "Open" and appears in the enquiry list

**QA:** `Enquiry::create()` succeeds; status defaults to `open`; all required fields validated (category, people_id or caller name, reason_for_contact, action_taken, user_id, occurred_at); Filament form validation rejects missing required fields.

---

#### EQ-002: Enquiry category selection [Critical]
**Given** a user creating an enquiry
**When** they select a category from the dropdown
**Then** the following categories are available: Family Advice, Help for Self, School/Madrassa, Donation Offer, Food Bank Referral, Domestic Issues, Mental Health Support, General Community Support

**QA:** `EnquiryCategory` enum values match; Filament select field renders all 8 options with correct labels, colors, and icons.

---

#### EQ-003: Enquiry risk and safeguarding flags [Critical]
**Given** a frontline worker creating or editing an enquiry
**When** they set safeguarding flags or risk flags
**Then** the flags are stored and visually highlighted on the enquiry record

**QA:** `safeguarding_flags` (boolean) and `risk_flags` (text) are saved; Filament renders safeguarding flag with warning/danger indicator; only SG and AD roles see restricted risk details.

---

#### EQ-004: Enquiry referral tracking [Important]
**Given** an enquiry with a referral
**When** the referral type is set to "internal" or "external"
**Then** the referral destination field is displayed and the referral details are stored

**QA:** `ReferralType` enum accepts `internal` and `external`; `referral_destination` is conditionally required when referral_type is set; referral details stored on enquiry record.

---

#### EQ-005: Convert enquiry to service user [Critical]
**Given** an open enquiry
**When** a user with `ConvertToServiceUser:Enquiry` permission clicks "Promote to Service User"
**Then** a new People record with `is_service_user = true` and a ServiceUserProfile are created; the enquiry status changes to "Converted"; the original enquiry links to the new service user

**QA:** `ConvertToServiceUser` action creates People with `is_service_user = true`; creates ServiceUserProfile linked to the new People; sets `enquiry.status = converted` and `enquiry.converted_at`; links enquiry to service user; audit log entry created.

---

#### EQ-006: Enquiry status lifecycle [Critical]
**Given** an enquiry with status "Open"
**When** it is not converted to a service user
**Then** it can be closed with a reason; status transitions are: Open → Converted, Open → Closed

**QA:** `EnquiryStatus` enum allows `open`, `converted`, `closed`; status can be changed manually to "Closed"; status cannot be changed back from "Converted" or "Closed" by FL/AS/CO roles.

---

#### EQ-007: Enquiry search and filtering [Important]
**Given** a list of enquiries
**When** a user searches by category, status, date range, or caller name
**Then** the list is filtered to show matching enquiries only

**QA:** Filament table search and filter work for `category`, `status`, `occurred_at` (date range), and related People name; results are scoped to the current team.

---

#### EQ-008: Enquiry permissions by role [Critical]

| Role | Create | View | Update | Delete | Convert |
|------|--------|------|--------|--------|---------|
| FL | Yes | Yes | Yes | No | No |
| AS | No | Yes | No | No | Yes |
| CO | No | Yes | No | No | No |
| SG | No | Yes | No | No | Yes |
| MG | No | Yes | No | No | No |
| AD | Yes | Yes | Yes | Yes | Yes |

**QA:** Policy checks for each role; Filament hides/shows actions per role; direct HTTP requests return 403 for unauthorised actions.