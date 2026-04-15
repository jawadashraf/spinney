# Spinney Hill CRM - Progress Report

**Report Date:** 12 April 2026  
**Project Phase:** Phase 1 MVP  
**Duration:** Weeks 1-3 of 6-8 week timeline

---

## Executive Summary

The Spinney Hill CRM project is progressing on schedule. The foundational infrastructure is complete, including authentication, team-based tenancy, and the core enquiry management system. The team has successfully delivered the enquiry intake workflow with full conversion to service user records. We are now entering the case management phase.

---

## Completed Deliverables

### Sprint 1: Foundations✓ Complete

| Deliverable | Status |
|-------------|--------|
| Enquiry model and migration | ✅ Complete |
| EnquiryCategory enum with color/icon support | ✅ Complete |
| EnquiryPolicy with role-based authorization | ✅ Complete |
| Team tenancy and audit support traits | ✅ Complete |

### Sprint 2: Enquiries ✓ Complete

| Deliverable | Status |
|-------------|--------|
| Enquiry Filament resource with modular schema | ✅ Complete |
| Caller identification (create/link People records) | ✅ Complete |
| Comprehensive form with category, risk flags, referrals | ✅ Complete |
| "Promote to Service User" conversion action | ✅ Complete |
| Test coverage (17 Pest tests) | ✅ Complete |

### Sprint 3: Authentication & Aesthetics ✓ Complete

| Deliverable | Status |
|-------------|--------|
| Laravel Fortify integration | ✅ Complete |
| Filament Auth UI Enhancer (premium login/registration) | ✅ Complete |
| Custom layout variations (plain, guest2) | ✅ Complete |
| Application branding and URL macros | ✅ Complete |
| Filament Activity Log for audit trail | ✅ Complete |

---

## Current Sprint:Case Files & Restricted Notes

**Status:** In Progress  
**Started:** Week 4

### In Progress
- Service user profile enhancements
- Case file unified view
- Restricted notes implementation (visibility field for safeguarding)

---

## Remaining Work (Phase 1)

| Module | Priority | Estimated Effort |
|--------|----------|------------------|
| Notes with restricted visibility | High | 2-3 days |
| Case file unified view | High | 2-3 days |
| Appointments & Shared Diaries | High | 1 week |
| Engagement Log (Check-in/Aftercare) | Medium | 1 week |
| Directories (Partners, Education, Donors) | Medium | 1 week |
| Donations & Gift Aid tracking | Medium | 2-3 days |
| Safeguarding Flags & Alerts | High | 2-3 days |
| Reporting & Exports | Low | 1 week |

---

## Technical Metrics

| Metric | Value |
|--------|-------|
| Feature Tests | 41 tests |
| Models Implemented | 18 |
| Filament Resources | 15 |
| Migrations | 47 |

---

## Decisions & Rationale

| Decision | Rationale |
|----------|-----------|
| Separate `ServiceUserProfile` model | Extends People without altering core, allows specialized fields |
| Modular Filament schemas | Improved maintainability for complex forms |
| `array_values()` in custom field seeding | Fixed package bug with sort_order |
| **Team = Tenant, Department = Functional Team** | Clear separation of multi-tenancy (Team) from role-based groupings (Department) |

### Organizational Model Clarification

| Concept | Purpose |
|---------|---------|
| **Team** | Tenant/Organization boundary for data isolation |
| **Department** | Functional team within an organization (user grouping by role) |
| **ServiceTeam** | Service type classification (Assessment, D&A, Spiritual, Education, Aftercare) |

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Restricted notes complexity | Policy-based access control pattern already established |
| Multi-team note visibility | Visibility column with `standard`/`restricted` values |
| Automated safeguarding notifications | Planned for Phase 2 |

---

## Next Steps (Sprint 4)

1. Complete restricted notes visibility implementation
2. Build unified case file view for service users
3. Begin appointments and shared diaries module

---

## Budget Status

| Item | Status |
|------|--------|
| Total Project Fee | £4,000 |
| Phase 1 Progress | ~40% complete |
| Timeline | On track (Week 4 of 6-8) |

---

## Questions for Client

1. **Directory structure:** Should Partner and Education directories share the same fields, or do they require distinct data capture?
2. **Appointment notifications:** Should email confirmations be sent to service users, staff, or both?
3. **Safeguarding escalation:** Who should receive immediate alerts for high-risk flags?

---

*Report generated from project tracking docs. Contact the development team for technical details.*