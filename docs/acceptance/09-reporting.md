# Reporting & Exports Acceptance Criteria

**Module:** Reporting & Data Exports
**AC IDs:** RP-001 to RP-005
**Requirements ref:** §12 — Reporting, Data & Research

---

#### RP-001: Enquiry volume and category report [Important]
**Given** a manager (MG) or admin (AD) user
**When** they request an enquiry report for a date range
**Then** they see count of enquiries by category, status, and handling team member

**QA:** Filament report or widget showing aggregate enquiry data; filterable by `occurred_at` date range, `category`, `status`; data scoped to current team.

---

#### RP-002: Service user engagement report [Important]
**Given** a manager (MG) or admin (AD) user
**When** they request an engagement report
**Then** they see service user count by service team, engagement status, and date range

**QA:** Report showing `target_service_team` distribution and `engagement_status` counts; filterable by date range; scoped to current team.

---

#### RP-003: Donation and Gift Aid report [Important]
**Given** a fundraising (FR), manager (MG), or admin (AD) user
**When** they request a donation report
**Then** they see donation totals by period, donor, and Gift Aid status

**QA:** Donation report with totals and Gift Aid breakdown; exportable to CSV/Excel; scoped to current team or donor.

---

#### RP-004: Export functionality [Important]
**Given** any user with export permission on a resource
**When** they click the export button on a list view
**Then** the data is exported as a downloadable file (CSV or Excel)

**QA:** Filament export action generates file; export respects current filters and team scope; file contains all visible columns; large exports are queued.

---

#### RP-005: Report access permissions [Critical]

| Role | Enquiry Reports | Engagement Reports | Donation Reports | Export |
|------|----------------|--------------------|------------------|--------|
| FL | No | No | No | No |
| SG | Read (enquiries) | Read | No | No |
| FR | No | No | Read/Write | Yes (donors) |
| MG | Read | Read | Read | Yes |
| AD | Read/Write | Read/Write | Read/Write | Yes |

**QA:** Dashboard widgets and report pages restricted per role; export button visibility matches permission matrix.