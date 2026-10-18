# Progress Log

Date: 2026-02-08
Updated: 2026-10-18

## Current Sprint
- **Sprint**: Phase 3
- **Status**: In Progress
- **Next Task**: Digital Presence / Public landing pages.

## Accomplishments
### Sprint 1: Foundations (Enquiry)
- [x] Defined `Enquiry` model and migration with core attributes (category, narrative, risk, advice, referral, staff ownership).
- [x] Created `EnquiryCategory` backed enum with color/icon support.
- [x] Implemented `EnquiryPolicy` with Jetstream team role and record ownership checks.
- [x] Added `HasTeam` and `HasCreator` traits to the `Enquiry` model for tenancy and audit support.

### Sprint 2: Enquiries (Resource)
- [x] Implemented `EnquiryResource` with modular schema structure.
- [x] Created comprehensive form with caller identification (create/link `People`), enquiry details, and actions.
- [x] Established table with badge-based categorization, staff sorting, and status icon filters.
- [x] Added `EnquiryInfolist` for standardized viewing of logged enquiries.
- [x] Verified implementation with 17 Pest tests (rendering, validation, creation, and authorization).
- [x] **[Conversion Flow]** Implemented "Promote to Service User" action with modal form for consent and initial assessment.
- [x] **[Seeding Fix]** Resolved SQL `sort_order` error by ensuring integer indexes in `ServiceUserCustomFieldSeeder` and `CreateTeamCustomFields` listener.
- [x] Verified full lifecycle from Enquiry logging to Service User promotion with tests.

### Sprint 3: Authentication & Aesthetics (Current)
- [x] **[Authentication]** Integrated Laravel Fortify with Filament for robust security foundations.
- [x] **[UI/UX]** Integrated and configured `Filament Auth UI Enhancer` for customized, premium login and registration experiences.
- [x] **[Layouts]** Implemented `plain` and `guest2` layout variations for flexible content presentation.
- [x] **[Branding]** Updated application branding and optimized URL generation macros for cleaner environment-agnostic links.
- [x] **[Auditability]** Implemented Filament Activity Log for comprehensive tracking of model changes and user actions.

### Apr–May 2026: Tasks, Care Plans, Enquiries
- [x] **[Tasks]** Task types, department support, due dates; `Taskable`/`TaskUser` pivots assign team IDs.
- [x] **[Care Plans]** `ThirdPartyCarePlan` with service user contacts, media, and a relation manager on the service user.
- [x] **[Enquiries]** Phone field, reordered form, `SafeguardingFlagRaised` notification pipeline, follow-up action.
- [x] **[Docs]** Signed proposal, Phase 1 acceptance criteria (`acceptance/`), classes plan, enquiries user guide.

### May–Jul 2026: Appointments, Availability, Service Users
- [x] **[Appointments]** `Schedule` with recurrence preview, appointment booking, booked/cancelled/reminder emails.
- [x] **[Availability]** `MyScheduleResource` with approvals, Volunteer Liaison role, working-hours middleware (Europe/London).
- [x] **[Service Users]** Notes timeline, profile sync, ethnicity, postcode lookup, emergency contacts, temporary emails, tab navigation.
- [x] **[Quality]** Resolved 321 PHPStan level 7 errors.

### Jul–Aug 2026: Support Status, Tasks, Dashboards
- [x] **[Support]** Service user support status flagging, resolution tracking, needs-attention notifications.
- [x] **[Dashboards]** Manager Dashboard and `ManagerStatsOverview`.
- [x] **[Tasks]** Assignment/priority/note/due notifications, activity history timeline, role-restricted assignees.

### Sep–Oct 2026: Aftercare Calls and Telephony
- [x] **[Calls]** Call plans, calls and attempts, outcomes, overdue digests, Liaison Dashboard.
- [x] **[Calls]** Active plans always keep one open call; reminders run at UK local time.
- [~] **[Yeastar]** PBX Custom CRM integration (contact lookup, screen pop, call journal). Committed locally, not deployed.

### Oct 2026: Outreach Directory
- [x] **[Companies]** Outreach directory on `Company`: team-managed `CompanyType`, `MailingGroup` tags, active/inactive status, general email/phone/website/address.
- [x] **[Contacts]** Named contacts as `Professional` People with role, mailing-list consent, email-invalid and unsubscribed flags.
- [x] **[Engagement]** `CompanyEngagement` log of visits, talks, events and meetings.
- [x] **[Import/Export]** `CompanyImporter` (company plus optional contact per row, updates by name) and updated `CompanyExporter`.
- [x] **[Access]** `CompanyPermissions` limits the directory to managers and admins; panel home now sends each role to a page it can open.
- [x] **[Outreach emails]** `OutreachEmail` drafts with Fin Mail templates, rich text and images, attachments and merge tags; recipients by mailing group, type or company; send, test send, duplicate and save as template.
- [x] **[Quota]** `RecordOutgoingMail` logs every sent email; `MailQuota` keeps 30 of 100 daily sends for notifications and blocks oversized campaigns.
- [x] **[Unsubscribe]** Signed unsubscribe links and `List-Unsubscribe` one-click headers; unsubscribed and invalid addresses are skipped.
- [x] **[Fin Mail]** Installed; templates, themes, mail settings and sent-email log in the System Admin panel.
- [x] **[Verification]** Companies link to the organisation they are part of and carry verification status, note and source.
- [x] **[Leicester directory]** Client research workbook (138 mosques, madrasahs, prayer rooms) converted and imported.
- [x] **[Appointments]** Booking/cancellation/reminder emails to counsellor and attendee; counsellors' "My appointments"; Assessment and Aftercare types; audited status changes; session records (attended/no-show, note, next step) visible to counsellor, managers and admins; counsellor track on the Manager Dashboard.
- [x] **[Restricted notes]** Note visibility (standard/restricted); restricted notes readable by managers and admins only via a global scope and the policy, hidden from background jobs and AI summaries; opening one logged as `viewed`.
- [x] **[Appointments review]** Only approved availability is bookable; locks enforced for non-managers; counsellors and assessors read their own clients' records; appointment editing fixed (it could not save and would have cleared status and session notes); the Schedules tab no longer lists appointments; clearer labels.
- [x] **[Appointments guides]** Staff guide, manager & admin guide and one-page quick reference (PDF, with screenshots).
- [x] **[Donations & Gift Aid]** Donations from people or companies with purpose and payment method; Gift Aid declarations on the person's profile; Gift Aid status set from the covering declaration; donation report with filters, grouping, totals, Gift Aid value and export; managers and admins only, admins alone delete.
- [x] **[Outreach dashboard]** Directory reach and verification, engagements and outreach emails per month, coverage by type, companies not contacted in six months.
- [x] **[Reports and Exports]** Central `ReportsDashboard` page with team-scoped date range, category, status, and service team filters; Enquiry volume and category breakdown report (RP-001); Service user engagement distribution report (RP-002); Filament exports for Enquiries, Service Users, and Calls (RP-004); and role-based permissions matrix via `ReportPermissions` (RP-005) with 21 passing Pest tests.

## Decisions
| Date | Decision | Rationale | Owner |
| --- | --- | --- | --- |
| 2026-02-08 | Phase 1 MVP defined in `docs/phase-1-handoff.md` | Aligns to brief and weekly sprints | Product |
| 2026-02-08 | Used modular schemas for `EnquiryResource` | Improved maintainability and clarity for complex forms/tables | Antigravity |
| 2026-02-08 | Proposed explicit `EnquiryStatus` and Service User fields for `People` | Ensures compliance with handoff requirements for consent and case management | Antigravity |
| 2026-02-08 | Use `array_values()` in `CreateTeamCustomFields` listener | Resolves package bug where string keys were intermittently used as `sort_order` for custom field options. | Antigravity |
| 2026-04-12 | **Clarified organizational model**: Team= Tenant/Organization, Department = Functional Team, ServiceTeam = Service Type | Corrects terminology for multi-tenancy and role-based access | Product |
| 2026-05-05 | Proposal signed: £3,600 over three phases, 50% on acceptance, 50% on completion | Charity/equity model with revenue share | Product |
| 2026-05-24 | Queue worker scheduled every minute instead of Horizon | Hostinger shared hosting has no long-running processes | Dev |
| 2026-09-15 | An active call plan always keeps one open call | Aftercare follow-ups cannot silently stop | Dev |
| 2026-10-08 | Yeastar via Custom CRM template; PBX never creates records; recordings stored as links | Data protection and record integrity | Dev |
| 2026-10-16 | Outreach directory built on Companies (Organisation = tenant Team); managers and admins only | Avoids clashing with tenancy terminology; directory holds external contact details | Product |
| 2026-10-18 | Restricted notes readable by managers and admins only (no separate safeguarding role) | They oversee all of a service user's activity | Product |
| 2026-10-18 | Appointments stay in fixed 60-minute slots; only approved availability is bookable; counsellors may read their own clients' records | Agreed while writing the appointments guide | Product |
| 2026-10-18 | Donations for managers and admins only (no fundraising role); a donor is anyone with a donation, not a People type; Gift Aid tracked through per-person declarations; company gifts are never Gift Aid | Keeps donor details from other staff; a service user or relative can also donate | Product |
| 2026-10-16 | Appointment session notes visible to the counsellor, managers and admins only | Counselling notes are sensitive (requirements §7) | Product |
| 2026-10-16 | Outreach emails via Fin Mail and the Hostinger mailbox, 100/day with 30 kept for notifications; no open/click tracking; bounces marked by hand | Low volume; Postmark can replace SMTP later | Product |
| 2026-10-18 | ReportsDashboard isolates report widgets with `$isDiscovered = false` | Prevents aggregate report widgets from auto-loading on operational/liaison dashboards | Dev |
| 2026-10-18 | Report & Export permissions centralized in `ReportPermissions` | Implements RP-005 matrix with role fallbacks for teams without granular Shield records | Dev |

## Risks and Blockers
- **Test suite**: 123 of 732 tests failing, mostly inherited Relaticle tests out of step with the schema (12 fixed on 18 Oct by giving the note factory a body).
- **Production**: `APP_DEBUG` was found enabled in October; confirm it is off.
- **Yeastar**: awaiting supplier confirmation of PBX plan and number formats.
- **Live data on the server**: the Hostinger database holds real service user data. Never run `migrate:fresh`, `migrate:reset`, `db:wipe` or reseeding there. Every migration must only add tables or columns, or seed in a way that is safe to repeat. New work is tested by pushing to `main` and deploying.

## Open Client Questions
- **Internal care plans and assessments**: wanted, or are external care plans enough?
- **Donations** (built 18 Oct; no further changes until agreed with the client):
  - Gift Aid declarations and donation tabs for service users (not possible yet: service users are not on the People list).
  - A "mark selected as Claimed" action for finishing a Gift Aid claim (now one donation at a time).
  - Whether adding a declaration that covers past gifts should update earlier "Not eligible" donations automatically.
  - The list of donation purposes (now: general funds, zakat, sadaqah, food bank, appeal or campaign, other).
  - Whether Claimed donations should be locked against edits.
  - Whether people already tagged as donors on the live database need reviewing.

## Production and Deployment
- Hosted on Hostinger shared hosting, not Docker. The Dockerfile, `docker-compose.prod.yml`, Horizon config and `docker-publish.yml` are left over from Relaticle.
- App path `~/domains/j-commerce.co.uk/public_html/spinney`. Deploy: pull the code, then `php artisan optimize:clear` and any new migrations.
- No long-running processes: the scheduler runs `queue:work --stop-when-empty` every minute, from a Hostinger cron that runs `php artisan schedule:run`. If `schedule:list` shows "Has Mutex" and no worker is running, clear it with `php artisan schedule:clear-cache`.
- Outreach email still needs production `MAIL_*` settings for the Hostinger mailbox, `php artisan storage:link`, and the real daily limit checked in hPanel.

## Notes
- `EnquiryPolicy` uses strict authorization mode, requiring comprehensive ability mapping (e.g., `deleteAny`).
- Filament v4 `Toggle` component uses `onColor()` instead of `color()`.
- Seeding robustness improved by forcing numeric keys for Choice custom field options.
