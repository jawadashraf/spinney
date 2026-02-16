# Phase 1 Handoff Plan: Spinney Hill Integrated CRM

Date: 2026-02-05
Owner: Product and Engineering
Scope: Phase 1 MVP only

**Project Context**
Goal: deliver a secure, role-based CRM that centralizes enquiries, case files, appointments, collaboration, aftercare, directories, safeguarding, and baseline reporting.
Tech stack: Laravel 12, Filament 4, Livewire 3, Filament Shield (Spatie Permissions), Pest 4, Tailwind CSS 4, PHP 8.4.
Tenancy model: team-based, enforced via global scopes.

**Success Criteria**
- All enquiries are logged in one place.
- Teams collaborate on shared client records.
- Appointments and referrals are streamlined.
- Data supports funding, governance, and learning.
- Staff experience reduced admin and better coordination.
- Service users receive joined-up, dignified support.

**Phase 1 Scope**
- Enquiries intake and conversion to service users.
- Service user registration and shared case file.
- Case notes with restricted access for safeguarding leads.
- Appointments and shared diaries with email confirmations.
- Telephone check-in and aftercare logs.
- Partner, education/outreach, and donor/supporter directories.
- Donation history with Gift Aid status.
- Safeguarding flags and consent tracking.
- Baseline reporting and exports.
- Full audit trail for access and changes.

**Phase 2 Out of Scope**
- Workflow automation and task orchestration.
- Advanced outcomes and progress tracking.
- Advanced dashboards and analytics beyond baseline reports.
- SMS reminders and external integrations.

**Modules and Acceptance Criteria**

**Module: Enquiries**
Scope
- Capture all incoming enquiries with category, caller details, reason, risk flags, advice/action, referral, staff handler, date/time.
- Store for analysis, reporting, and research.
Data
- New model `Enquiry` with fields: category, caller_name, caller_contact, reason, risk_flags, advice_action, referral_type, referral_details, handled_by_id, occurred_at, status, converted_service_user_id.
Permissions
- Frontline can create and view all enquiries.
- Other teams can view enquiries.
Acceptance Criteria
- Enquiry can be created, updated, and searched.
- Enquiry can be converted into a service user record.
- All required fields are validated.

**Module: Service Users and Case File**
Scope
- Register service users, maintain a single shared record.
- Capture consent and GDPR status, presenting issues, risks.
Data
- Reuse `People` model.
- Add custom fields: consent_data_storage, consent_referrals, consent_communications, presenting_issues, risk_summary, faith_cultural_sensitivity, service_team, engagement_status.
Permissions
- All case-working teams can view assigned service users.
- Safeguarding leads can view all service users.
Acceptance Criteria
- Service user record is created from enquiry or directly.
- Consent and risk fields are captured and reported.

**Module: Notes and Collaboration**
Scope
- Teams add and view case notes across service users.
- Sensitive notes are restricted to safeguarding leads.
Data
- Reuse `Note` model.
- Add `visibility` column with values `standard` and `restricted`.
- Add custom fields: service_type, note_type.
Permissions
- Restricted notes visible to safeguarding leads only.
- Standard notes visible to all case-working teams on the record.
Acceptance Criteria
- Notes are time-stamped and linked to author.
- Restricted notes are not visible to non-safeguarding roles.

**Module: Appointments and Shared Diaries**
Scope
- Telephone team can book appointments for assessment, D&A, spiritual counselling, education/support.
- Bookings trigger internal notifications and email confirmations.
Data
- New model `Appointment` with fields: service_user_id, type, starts_at, ends_at, booked_by_id, assigned_to_id, status, location, notes.
Permissions
- Telephone team can create and manage appointments.
- Assigned team members can view their appointments.
Acceptance Criteria
- Appointment can be created and updated.
- Email confirmation is sent on booking.

**Module: Telephone Check-In and Aftercare**
Scope
- Log check-ins, follow-ups, outcomes, and next steps.
Data
- New model `EngagementLog` with fields: service_user_id, type, outcome, next_steps, occurred_at, staff_id.
Permissions
- Aftercare team can create and view engagement logs.
Acceptance Criteria
- Engagement history is visible on the service user record.
- New logs are time-stamped and linked to author.

**Module: Directories**
Scope
- Partner organisations directory.
- Education and outreach directory.
- Donors and supporters directory with Gift Aid status.
Data
- Reuse `Company` for partner and education directories.
- Reuse `People` for donors and supporters.
- Add custom fields to identify directory type and capture referral criteria and engagement history.
Permissions
- Fundraising and Admin can access donor data.
- Outreach team can access education directory.
Acceptance Criteria
- Directory entries are searchable and exportable.

**Module: Donations and Gift Aid**
Scope
- Capture donor details, donation history, Gift Aid consent/status, purpose of donations.
Data
- New model `Donation` with fields: donor_type, donor_id, amount, currency, donated_at, gift_aid_status, purpose.
Permissions
- Fundraising and Admin only.
Acceptance Criteria
- Donations can be reported by period and donor.

**Module: Safeguarding and Consent**
Scope
- Safeguarding flags and alerts.
- Consent tracking for data storage, referrals, communications.
Data
- New model `SafeguardingFlag` with fields: service_user_id, enquiry_id, severity, details, status, created_by_id, resolved_at.
Permissions
- Safeguarding leads only for flags and restricted notes.
Acceptance Criteria
- Flags can be created and resolved.
- Consent status appears in the service user record.

**Module: Reporting and Exports**
Scope
- Reporting on enquiries, uptake/referrals, engagement/outcomes, donations/Gift Aid.
Data
- Aggregate queries across Enquiry, People, EngagementLog, Donation.
Permissions
- Management and Admin.
Acceptance Criteria
- Reports can be exported for funders and trustees.

**Module: Audit Trail**
Scope
- Full audit trail of access and changes, utilizing `spatie/laravel-activitylog` integrated into Filament.
Data
- Activity Log entries including user_id, action (description), subject_type, subject_id, properties (metadata), and timestamps.
Permissions
- Management and Admin.
Acceptability Criteria
- Model changes (create, update, delete) are automatically logged.
- Significant system events and unauthorized access attempts are tracked.

**Permissions Matrix**
Role definitions are Spatie Permission roles managed via Filament Shield. Roles are seeded and permissions assigned through the Shield admin UI or programmatically.

| Role | Access Summary |
| --- | --- |
| Frontline | Enquiries create/read, appointments book, service users read, standard notes create/read |
| Assessment | Assigned service users read/write, appointments read/write, standard notes create/read |
| DrugAlcohol | Assigned service users read/write, appointments read/write, standard notes create/read |
| Spiritual | Assigned service users read/write, appointments read/write, standard notes create/read |
| EducationOutreach | Directories read/write, assigned service users read, standard notes create/read |
| Aftercare | Service user contact read, engagement logs create/read, standard notes read |
| Safeguarding | Full service user access, restricted notes, safeguarding flags |
| Fundraising | Donors and donations only |
| Management | Read-only across records, reporting and exports |
| Admin | Full access |

**Data Model Overview**

| Model | Type | Relationships |
| --- | --- | --- |
| People | Existing | Notes, Company, EngagementLog, Appointments |
| Company | Existing | People, Notes |
| Note | Existing | People, Company |
| Enquiry | New | BelongsTo People, BelongsTo User |
| Appointment | New | BelongsTo People, BelongsTo User |
| EngagementLog | New | BelongsTo People, BelongsTo User |
| SafeguardingFlag | New | BelongsTo People, BelongsTo Enquiry, BelongsTo User |
| Donation | New | MorphTo donor (People or Company) |
| AuditLog | New | MorphTo auditable |

**Sprint Plan (Weekly)**

**Sprint 1: Foundations**
- Create migrations and models for Enquiry, Appointment, EngagementLog, SafeguardingFlag, Donation, AuditLog.
- Add custom fields to People and Note.
- Add `visibility` to notes and policy logic for restricted notes.
- Seed Spatie roles via Filament Shield and assign granular permissions.
- Add tenant scopes for new models.
- Add factories and seeders for new models.
- Tests: feature tests for role access and model validation.

**Sprint 2: Enquiries**
- Enquiry Filament resource and forms.
- Conversion flow to service user.
- Search and filters.
- Tests: create, update, convert, and permission checks.

**Sprint 3: Service Users and Notes**
- Service user profile fields and layout.
- Case notes with restricted visibility.
- Tests: restricted notes visibility, audit logging for note access.

**Sprint 4: Appointments and Aftercare**
- Appointment scheduling and shared diary views.
- Email confirmations for booking.
- Aftercare logs with outcomes and next steps.
- Tests: appointment permissions and notification dispatch.

**Sprint 5: Directories and Donations**
- Partner, education, and donor directories.
- Donations and Gift Aid tracking.
- Tests: directory permissions, donation reporting.

**Sprint 6: Reporting and Pilot Readiness**
- Reporting dashboards and exports.
- Audit trail coverage for core records.
- Pilot checklist and data entry guidance.
- Tests: report queries and export coverage.

**Test Plan**
- Use Pest for all tests.
- Run targeted tests: `php artisan test --compact tests/Feature/...`
- Run formatting: `vendor/bin/pint --dirty` before completion.

**Definition of Done**
- Required data model and migrations merged.
- Filament resources provide create, view, update, list.
- Access control enforced by policies and tests.
- Email reminders tested with fakes.
- Reports export successfully.
- All new tests pass.

**Open Decisions**

| Decision | Options | Recommendation | Owner |
| --- | --- | --- | --- |
| Aftercare logs | Separate EngagementLog model or Notes with note_type | Separate model for clean reporting | Product |
| Directory models | Reuse People/Company or separate models | Reuse for Phase 1 to reduce scope | Product |

**Implementation Notes**
- Use `php artisan make:` commands with `--no-interaction`.
- Use `search-docs` for Laravel, Filament, Livewire, and Pest guidance.
- Do not add new base folders without approval.
