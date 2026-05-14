# Phase 1 Acceptance Criteria & Sign-off

**Project:** Spinney Hill Support Centre CRM
**Document Version:** 1.0
**Date:** 2026-05-05
**Scope:** Phase 1 MVP
**Status:** Draft

---

## Document Structure

Each module has its own acceptance criteria file:

| File | Module | AC IDs |
|------|--------|--------|
| [00-cross-cutting.md](00-cross-cutting.md) | Authentication, Tenancy, RBAC, Audit | CC-001 to CC-007 |
| [01-enquiries.md](01-enquiries.md) | Enquiry Management | EQ-001 to EQ-008 |
| [02-service-users.md](02-service-users.md) | Service Users & Case File | SU-001 to SU-007 |
| [03-notes.md](03-notes.md) | Notes & Collaboration | NT-001 to NT-007 |
| [04-appointments.md](04-appointments.md) | Appointments & Shared Diaries | AP-001 to AP-007 |
| [05-aftercare.md](05-aftercare.md) | Telephone Check-In & Aftercare | AF-001 to AF-006 |
| [06-directories.md](06-directories.md) | Directories (Partner, Education, Donor) | DR-001 to DR-005 |
| [07-donations.md](07-donations.md) | Donations & Gift Aid | DN-001 to DN-004 |
| [08-safeguarding.md](08-safeguarding.md) | Safeguarding & Consent | SG-001 to SG-007 |
| [09-reporting.md](09-reporting.md) | Reporting & Exports | RP-001 to RP-005 |
| [10-audit-trail.md](10-audit-trail.md) | Audit Trail | AT-001 to AT-004 |
| [11-non-functional.md](11-non-functional.md) | Non-Functional (Performance, Security, Browsers) | NF-001 to NF-006 |
| [12-classes.md](12-classes.md) | Classes & Group Sessions | CL-001 to CL-020 |

---

## Priority Legend

| Priority | Meaning |
|----------|---------|
| **Critical** | Must-pass for Phase 1 sign-off. Blocker if failed. |
| **Important** | Should-pass. Workaround acceptable for launch. |
| **Desirable** | Nice-to-have. May be deferred to post-launch. |

---

## Role Abbreviations

| Abbr | Role | Access Summary |
|------|------|----------------|
| FL | Frontline | Enquiries create/read, appointments book, service users read, standard notes create/read |
| AS | Assessment | Assigned service users read/write, appointments read/write, standard notes create/read |
| CO | Counselor | Assigned service users read/write, appointments own schedules, notes create/read |
| AC | Aftercare | Service user contact read, engagement logs create/read, standard notes read |
| SG | Safeguarding | Full service user access, restricted notes, safeguarding flags |
| FR | Fundraising | Donors and donations only |
| MG | Management | Read-only across records, reporting and exports, lock/unlock |
| AD | Admin | Full access |

---

## UAT Scenarios (Client Sign-off)

| ID | Scenario | Maps to |
|----|----------|---------|
| UAT-001 | Frontline worker logs a new phone enquiry in under 60 seconds | EQ-001 |
| UAT-002 | Safeguarding lead sees a flag that a practitioner cannot see | SG-001, SG-002, NT-002, NT-003 |
| UAT-003 | Practitioner views complete client history across teams (excluding restricted notes) | SU-007, NT-001 |
| UAT-004 | Admin exports a report of last month's enquiries for a funding board | RP-001, RP-004 |
| UAT-005 | Enquiry is converted to a service user record with automatic data transfer | EQ-005, SU-001 |
| UAT-006 | Appointment is booked with a counselor and email confirmation sent | AP-001, AP-004 |
| UAT-007 | Aftercare check-in auto-generates follow-up task based on engagement status | AF-001, AF-002, AF-003 |
| UAT-008 | Donation recorded with Gift Aid status visible in donor reporting | DN-001, DN-002, DN-003 |
| UAT-009 | Each role sees only the navigation items, data, and actions they are authorised for | CC-004, all module permission ACs |
| UAT-010 | Two organisations on the same CRM instance cannot see each other's data | CC-003, NF-003 |
| UAT-011 | Counselor creates a class, manager publishes it, and a service user books a free drop-in session | CL-001, CL-003, CL-005, CL-008, CL-009 |
| UAT-012 | Outsider books a paid class online, pays via Stripe, and attendance is marked after the session | CL-010, CL-013, CL-014, CL-017 |

---

## Sign-off Matrix

### Module Sign-off

| Module | AC IDs | Client Sign-off | QA Sign-off | Dev Sign-off | Date |
|--------|--------|-----------------|-------------|--------------|------|
| Authentication & Access | CC-001 to CC-007 | | | | |
| Enquiries | EQ-001 to EQ-008 | | | | |
| Service Users & Case File | SU-001 to SU-007 | | | | |
| Notes & Collaboration | NT-001 to NT-007 | | | | |
| Appointments & Shared Diaries | AP-001 to AP-007 | | | | |
| Telephone Check-In & Aftercare | AF-001 to AF-006 | | | | |
| Directories | DR-001 to DR-005 | | | | |
| Donations & Gift Aid | DN-001 to DN-004 | | | | |
| Safeguarding & Consent | SG-001 to SG-007 | | | | |
| Reporting & Exports | RP-001 to RP-005 | | | | |
| Audit Trail | AT-001 to AT-004 | | | | |
| Non-Functional | NF-001 to NF-006 | | | | |
| Classes & Group Sessions | CL-001 to CL-020 | | | | |

### Overall Phase 1 Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Client Representative | | | |
| Project Manager | | | |
| Lead Developer | | | |
| QA Lead | | | |

---

## Traceability Matrix

| Requirement Section | AC Module | AC IDs |
|---------------------|-----------|--------|
| §2 — Project Aim | CC | CC-001 to CC-007 |
| §3 — Key Users / Roles | CC | CC-004, CC-007 |
| §4 — Enquiry Management | EQ | EQ-001 to EQ-008 |
| §5 — Service User Registration | SU | SU-001 to SU-007 |
| §7 — Multi-Team Notes | NT | NT-001 to NT-007 |
| §6 — Appointments & Diaries | AP | AP-001 to AP-007 |
| §8 — Check-In & Aftercare | AF | AF-001 to AF-006 |
| §9 — Directories | DR | DR-001 to DR-005 |
| §9.3 — Donors & Gift Aid | DN | DN-001 to DN-004 |
| §11 — Safeguarding & Consent | SG | SG-001 to SG-007 |
| §12 — Reporting | RP | RP-001 to RP-005 |
| §11 — Audit Trail | AT | AT-001 to AT-004 |
| §13.2 — Specialized Care Roles | CL | CL-001 to CL-020 |
| §13.5 — Payment Tracking | CL | CL-001 to CL-020 |

---

*Refer to `docs/requirements-extracted.md` for the full requirements specification.*