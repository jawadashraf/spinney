# Module Checklist (Definition of Done)

Date: 2026-02-05
Updated: 2026-10-16

**Terminology**
- **Team** = Tenant/Organization (multi-tenancy boundary)
- **Department** = Functional team within an organization
- **ServiceTeam** = Service type classification

**Legend**
- [ ] Not started
- [~] In progress / partial
- [x] Done

**Scope key** (signed proposal, 5 May 2026)
- **P1** Phase 1: Foundation & Core · **P2** Phase 2: Practice Management · **P3** Phase 3: Engagement & Ops
- **Extra** Built beyond the signed scope · **Future** Not in the signed scope (quote separately)

## Enquiries (P1)
- [x] Data model and migration complete
- [x] Filament resource created
- [x] Conversion to service user implemented
- [x] Validation and error handling
- [x] Permissions enforced
- [~] Reporting included (dashboard widgets; no export yet)
- [x] Tests added and passing

## Service Users and Case File (P1)
- [x] People custom fields added (`ServiceUserProfile` model)
- [x] Case file layout and UI (tabbed form, notes timeline, care plans tab)
- [x] Consent tracking visible
- [x] Risk and presenting issues captured
- [x] Permissions enforced (volunteer liaisons see assigned records only)
- [x] Tests added and passing

## Care Plans (P2)
- [x] External (third-party) care plans with documents
- [ ] Internal care plans (confirm with client whether required)
- [~] Assessments (captured on the service user profile, no separate assessment record)

## Notes and Collaboration (P2)
- [x] Visibility field added (`standard` vs `restricted`) (NT-001, NT-002)
- [x] Restricted notes readable by managers and admins only (decided 18 Oct 2026; no separate safeguarding role) (NT-002, NT-003)
- [x] Hidden from every list, export, widget and AI summary for other staff, and from queued/background work (NT-003)
- [x] Opening a restricted note (view or edit) logged as `viewed` in the audit log (NT-004, AT-002)
- [~] Note type and service type fields (searchable; no category field)
- [~] Edit/delete limited to own notes (NT-007): not yet enforced; permissions are role-based
- [x] Tests added and passing

## Appointments and Shared Diaries (P2)
Reviewed 16 Oct 2026 against `acceptance/04-appointments.md`; gaps fixed the same day. Reviewed again on 18 Oct while writing the user guides; gaps fixed.
- [x] Data model and migration complete (`Schedule`) (AP-001)
- [x] Booking UI: counsellor, attendee, type, date and free 60-minute slot from availability (AP-001, AP-003); service user page books through the same path
- [x] Availability management (My Schedule with approvals, working hours)
- [x] Only approved availability is bookable; availability added by managers is approved at once; existing availability marked approved on 18 Oct
- [x] Locked availability can only be changed or deleted by managers and admins; Locked badge shown
- [x] Appointment types incl. Assessment and Aftercare (AP-002)
- [x] Email confirmations on booking and cancellation, and day-before reminders, to the counsellor and the attendee's own email (AP-004); service users only with communications consent and a real address
- [x] Status transitions with audit log; status shown in list (AP-005); scheduled → completed also allowed
- [x] Counsellors see and manage their own appointments ("My appointments"); assessors view own (AP-006)
- [x] Counsellors and assessors can read the records of their own clients (read-only), and no others
- [x] Editing an appointment changes details only and keeps its slot, status and session record; to move one, cancel and rebook
- [x] Session record on completion: attended/no-show, session note, next step; notes visible to the counsellor, managers and admins only
- [x] Counsellor track on the Manager Dashboard: sessions held, no-shows, cancellations, upcoming, needs recording
- [ ] Shared calendar view (AP-007, Desirable; needs a calendar plugin)
- [x] Permissions enforced
- [x] Tests added and passing

## Telephone Check-In and Aftercare (P2 reminders, Extra)
- [x] Data model and migration complete (call plans, calls, attempts)
- [x] Engagement log UI (calls and outcomes, Liaison Dashboard)
- [x] **Automated follow-up task generation**:
    - [x] Weekly/fortnightly/monthly/quarterly cadence per call plan
    - [x] Next call booked automatically when a call is closed
- [x] **Due/Overdue alert system** (daily digests at UK time)
- [x] Outcomes and next steps captured
- [x] Permissions enforced
- [x] Tests added and passing
- [~] Yeastar PBX integration (committed locally, not deployed)

## Directories (P2 partners, P3 outreach)
- [x] Outreach directory (Companies): team-managed types (Charity / Partner, Education Institute, Masjid, Madrassa, custom)
- [x] Named contacts (People of type `professional`), mailing groups, active/inactive status
- [x] Engagement history log (visits, talks, events, meetings)
- [x] Spreadsheet import and CSV export
- [x] Permissions enforced (managers and admins only)
- [x] Tests added and passing
- [x] Outreach emails from the CRM: Fin Mail templates, rich text with images, attachments, personalisation, recipients by group/type/company, daily quota, unsubscribe link, per-company email history
- [x] Part of (parent organisation), verification status/note/source, "No email yet" filter
- [x] Outreach dashboard: reach, verification, engagements and emails per month, coverage by type, not contacted in 6 months
- [ ] Production mail set-up (Hostinger SMTP credentials, `storage:link` for images) and a live test send
- [x] Donors: anyone (person or company) with a donation; Donors tab on People for managers and admins (18 Oct)

## Donations and Gift Aid (P3)
Built 18 Oct 2026 against `acceptance/07-donations.md`. Managers and admins only (no fundraising role).
- [x] Data model and migration complete (`Donation`, `GiftAidDeclaration`; new tables only) (DN-001)
- [x] Donations from people or companies: amount, date, payment method, purpose, reference (DN-001)
- [x] Donation history UI: Donations list, and Donations tab on person and company profiles
- [x] Gift Aid declarations on the person's profile: date, method, past/future cover, declared address, withdrawal (DN-002)
- [x] Gift Aid status per donation, set from the declaration covering its date; company gifts never Gift Aid; withdrawal releases later unclaimed gifts (DN-002)
- [x] Report: filters by date, donor, Gift Aid status and purpose; group by donor, purpose or Gift Aid; totals and Gift Aid value; CSV/Excel export (DN-003)
- [x] Permissions enforced: managers record, edit and report; only admins delete; declarations with donations cannot be deleted (DN-004)
- [x] Tests added and passing

## Safeguarding and Consent (P1)
- [x] Safeguarding flags model and UI (enquiry flags, service user support status)
- [x] Alerting rules defined (safeguarding and needs-attention notifications)
- [x] Restricted notes access enforced (managers and admins)
- [x] Consent tracking fields defined
- [x] Tests added and passing

## Team Management & Tasks (Extra)
- [x] Team management module implemented (departments, roles)
- [x] Task assignment to individuals within teams
- [x] Task alerts (attention, due time)
- [x] Permissions enforced
- [x] Tests added and passing

## Care Timeline & Session Logs (Future)
- [ ] Session log model for Check-In, Spiritual, and Therapy
- [~] Shared timeline view for service users (notes and activity timeline)
- [ ] Therapist "suggest action" functionality
- [ ] Restricted view permissions for session logs
- [ ] Tests added and passing

## Dynamic Scheduled Forms (Future)
- [ ] Dynamic form builder/structure
- [ ] Daily/Weekly schedule generation
- [ ] Service user update UI
- [ ] Reminder and motivation logic
- [ ] Team alerts based on submissions
- [ ] Tests added and passing

## Multi-Channel Notifications (P1)
- [x] Live notifications with actionable links
- [x] Targeted database notifications with filtering
- [x] Email notification system (queued via scheduled worker)
- [x] Tests added and passing

## Payments & Charging (Future)
- [ ] Therapist session charging logic
- [~] Online payment tracking (appointments marked free/paid only)
- [ ] Offline & Bank Transfer recording
- [ ] Permissions enforced
- [ ] Tests added and passing

## Classes & Group Sessions (Future)
- [~] Implementation plan and acceptance criteria written (`implementation-plan-classes.md`, `acceptance/12-classes.md`)
- [ ] Not built

## AI Engagement (Chatbot) (Future)
- [ ] AI Chatbot frontend interface
- [ ] Intent classification and lead capture logic
- [ ] Automated Enquiry creation from chat sessions
- [ ] Permissions and oversight views
- [ ] Tests added and passing

## Multi-Dashboard Infrastructure (P3)
- [~] Role-specific dashboard system (Manager and Liaison dashboards)
- [x] Multiple dashboards for managing admin
- [x] Custom metric/widget support
- [x] Permissions enforced
- [x] Tests added and passing

## Reporting and Exports (P3)
- [~] Baseline reports implemented (enquiry, call and workload widgets)
- [ ] Export functionality available (CSV/PDF for enquiries, service users, calls; donations CSV/Excel done 18 Oct)
- [ ] Payment and engagement reports
- [ ] Permissions enforced
- [ ] Tests added and passing

## Digital Presence (P3)
- [ ] Public landing pages: Home, About, Contact, How we work (still the Relaticle template)

## Audit Trail (P1)
- [x] Audit log model and storage
- [x] Logging for create/update/delete
- [x] Logging for access to restricted notes (appointment session notes are gated, not logged)
- [x] Admin/management views (System Admin activity log and log viewer)
- [x] Tests added and passing

## Handover
- [~] Training documentation: enquiries user guide; call management guides; appointments staff guide, manager guide and quick reference (18 Oct); donations manager & admin guide (18 Oct)
- [ ] In-app documentation rewritten for Spinney Hill (still Relaticle text)
- [ ] UAT against `acceptance/` criteria and client sign-off
