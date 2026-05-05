# Directories Acceptance Criteria

**Module:** Partner, Education & Outreach, Donor Directories
**AC IDs:** DR-001 to DR-005
**Requirements ref:** §9 — Directories & Relationship Management

---

#### DR-001: Partner organisation directory [Important]
**Given** a user with directory access (CO, SG, MG, AD)
**When** they access the partner directory
**Then** they can create, view, search, and export partner organisation records with: name, address, contact details, type of support offered, referral criteria, notes, and referral history

**QA:** Company model with `type = partner` or custom field identifying directory type; Filament CompanyResource with partner-specific fields; search by name and type; export functionality available to MG/AD.

---

#### DR-002: Education & outreach directory [Important]
**Given** a user with directory access
**When** they access the education directory
**Then** they can create, view, search, and export records for madrassas, schools, colleges, and youth organisations including: key contacts, email addresses, engagement history

**QA:** Company model with `type = education` or custom field; key contacts stored as related People records; engagement history via notes relation; searchable by name and type.

---

#### DR-003: Donor & supporter directory [Important]
**Given** a fundraising (FR) or admin (AD) user
**When** they access the donor directory
**Then** they can create, view, search, and export donor/supporter records

**QA:** People records with `type` indicating donor role; FR can view/create donors; FR cannot see other record types; AD has full access; donor search and export available.

---

#### DR-004: Directory export [Important]
**Given** a user with export permission (MG, AD)
**When** they click export on a directory
**Then** the directory data is exported as CSV or Excel

**QA:** Filament export action generates downloadable file; export includes all visible fields; export scoped to current team.

---

#### DR-005: Directory permissions by role [Critical]

| Role | Partners | Education | Donors |
|------|----------|-----------|--------|
| FL | Read | Read | No |
| CO | Read/Write | Read/Write | No |
| AC | Read | Read | No |
| FR | No | No | Read/Write |
| MG | Read | Read | Read |
| AD | Full | Full | Full |

**QA:** CompanyPolicy and PeoplePolicy enforce per-role directory access; navigation menu items hidden for roles without access; direct URL access returns 403.