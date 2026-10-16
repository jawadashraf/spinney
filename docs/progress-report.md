# Spinney Hill CRM - Progress Report

**Report Date:** 16 October 2026
**Agreement:** Signed proposal, 5 May 2026 (£3,600, three phases)
**Previous Report:** 12 April 2026

---

## Executive Summary

Phase 1 is complete and most of Phase 2 is in place. Since the last report the system has gained a full service user case file, appointments with availability management, support and safeguarding flags, a task system, and an aftercare call management system with automated reminders. A Yeastar phone system integration is built and awaiting deployment.

The main outstanding work is in Phase 3: donations, outreach, exports and the public website. Restricted notes are the remaining Phase 2 item. The outreach directory was completed on 16 October.

---

## Progress Against the Signed Proposal

### Phase 1: Foundation & Core ✅ Complete

| Deliverable | Status |
|-------------|--------|
| Laravel / Filament v5, authentication, roles and permissions | ✅ Complete |
| Enquiries: intake form, status tracking, follow-ups | ✅ Complete |
| Enquiries: automated safeguarding notifications | ✅ Complete |
| Client records: service user case file with profile, consent, risk, GP and emergency contacts | ✅ Complete |
| Postcode address lookup | ✅ Complete |

### Phase 2: Practice Management 🟡 Mostly complete

| Deliverable | Status |
|-------------|--------|
| External (third-party) care plans with documents | ✅ Complete |
| Counsellor appointments with booking, cancellation and reminder emails | ✅ Complete |
| Availability management (My Schedule, approvals, working hours) | ✅ Complete |
| Reminder system (appointments, tasks, aftercare calls at UK time) | ✅ Complete |
| Interaction notes, searchable, with service user timeline | ✅ Complete |
| Restricted notes for safeguarding | ⏳ Not started |
| Outreach directory (Companies): types, contacts, mailing groups, engagement log, import/export | ✅ Complete (16 Oct) |
| Internal care plans and assessments | ❓ To confirm with client |

### Phase 3: Engagement & Ops ⏳ Mostly outstanding

| Deliverable | Status |
|-------------|--------|
| Role-specific dashboards (Manager, Liaison) | 🟡 Partial |
| System Admin panel with audit log | ✅ Complete |
| Donation tracking and Gift Aid | ⏳ Not started |
| Outreach emails from the CRM (Fin Mail, Hostinger mailbox, daily quota, unsubscribe) | ✅ Built (16 Oct); production mail set-up pending |
| Outreach programme engagement metrics | 🟡 Engagement log in place; reporting not started |
| Data exports (CSV/PDF) | ⏳ Not started |
| Public landing pages (Home, About, Contact, How we work) | ⏳ Not started |

---

## Delivered Since the Last Report

| Period | Area | Delivered |
|--------|------|-----------|
| Apr | Tasks | Task types, departments, due dates |
| Apr–May | Care plans | Third-party care plans with documents, shown on the service user record |
| May | Enquiries | Phone field, reordered form, safeguarding notifications, follow-up action |
| May–Jul | Appointments | Schedules with recurrence, appointment booking, My Schedule with approvals, Volunteer Liaison role, working hours |
| Jun–Jul | Service users | Notes timeline, postcode lookup, ethnicity, emergency contacts, temporary emails, tabbed form |
| Jun | Audit | Activity log and log viewer in System Admin |
| Jul–Aug | Support | Support status flagging with resolution history, manager alerts, Manager Dashboard |
| Aug | Tasks | Task notifications, due-date reminders, change history |
| Sep | Aftercare | Call plans, calls and attempts, due/overdue digests, Liaison Dashboard |
| Oct | Telephony | Yeastar PBX integration: caller lookup, screen pop, automatic call logging |

The aftercare call system, task management and Yeastar integration go beyond the signed scope.

---

## Remaining Work

| Item | Phase | Estimated Effort |
|------|-------|------------------|
| Outreach emails: production mail set-up and live test | P3 | 0.5 day |
| Restricted notes and access logging | P2 | 2–3 days |
| Donations, Gift Aid and donor directory | P3 | 3–4 days |
| Outreach engagement metrics | P3 | 1–2 days |
| Reports and exports (CSV/PDF) | P3 | 3–4 days |
| Public landing pages | P3 | 1–2 days |
| Automated test suite repairs | — | 1–3 days |
| Yeastar go-live (deploy, PBX configuration) | Extra | 1–2 days |
| Training manuals, UAT and sign-off | Handover | 3–4 days |
| **Total** | | **~14–21 working days** |

---

## Technical Metrics

| Metric | Value |
|--------|-------|
| Automated tests | 683 (540 passing, 135 failing, 7 skipped, 1 risky) |
| Models | 36 |
| Filament resources | 18 |
| Migrations | 62 (plus 5 settings migrations) |

The failing tests are mostly older tests inherited from the base template that no longer match the schema. They are scheduled for repair before sign-off.

---

## Decisions & Rationale

| Decision | Rationale |
|----------|-----------|
| Separate `ServiceUserProfile` model | Extends People without altering core, allows specialized fields |
| Team = Tenant, Department = Functional Team | Separates data isolation from role-based groupings |
| Queue run by the scheduler every minute | Hostinger shared hosting has no long-running worker |
| Reminders run at UK local time | Staff receive digests at the start of the UK working day |
| An active call plan always has one open call | Aftercare follow-ups cannot silently stop; only a manager pauses a plan |
| Yeastar via Custom CRM template, never creating records | The phone system must not create or change service user records |

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Restricted notes not yet in place | Scheduled as a Phase 2 priority |
| Hostinger free mailbox sends about 100 emails a day | Quota panel keeps 30 for notifications; upgrade or Postmark later |
| Debug mode found enabled in production (Oct) | Confirm `APP_DEBUG=false` on the server |
| Yeastar plan and number formats unconfirmed | Awaiting supplier answers before go-live |

---

## Questions for Client

1. **Care plans:** Are internal care plans and separate assessment records needed, or is the current service user profile sufficient?
2. **Directory spreadsheet:** Please share the existing outreach spreadsheet so the import can be checked against its columns.
3. **Classes & group sessions:** A plan exists but this is outside the signed scope. Should it be quoted as an additional feature?
4. **Safeguarding escalation:** Who should receive immediate alerts for high-risk flags?

---

## Budget Status

| Item | Status |
|------|--------|
| Total project fee | £3,600 |
| Initial payment (50%) | £1,800 on acceptance |
| Completion payment (50%) | £1,800 on completion |

---

*Report generated from project tracking docs. Contact the development team for technical details.*
