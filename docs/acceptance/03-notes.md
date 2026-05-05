# Notes & Collaboration Acceptance Criteria

**Module:** Multi-Team Notes & Collaboration
**AC IDs:** NT-001 to NT-007
**Requirements ref:** §7 — Multi-Team Notes & Collaboration

---

#### NT-001: Create a standard note [Critical]
**Given** a user with `Create:Note` permission (FL, AS, CO, SG)
**When** they create a note attached to a People or Company record
**Then** the note is saved with title, body, author, timestamp, and visibility = standard

**QA:** Note created with `creator_id = auth()->id()`, `visibility = standard`, `team_id = current_team_id`; timestamp auto-set; note appears on the related record's notes relation manager.

---

#### NT-002: Create a restricted note [Critical]
**Given** a safeguarding lead (SG) or admin (AD)
**When** they create a note with visibility = restricted
**Then** the note is saved and visible only to safeguarding leads and admins

**QA:** Note created with `visibility = restricted`; `NotePolicy::view()` returns false for non-SG/AD users; restricted notes hidden from FL/AS/CO/AC/FR/MG in Filament queries; restricted note creation UI only visible to SG/AD.

---

#### NT-003: Restricted note access enforcement [Critical]
**Given** a restricted note on a service user record
**When** a non-safeguarding user (FL, AS, CO, AC, FR, MG) attempts to view it
**Then** the note is not visible in any list, detail view, or API response

**QA:** Global scope on Note model filters `visibility = 'restricted'` for non-SG/AD roles; direct URL `/admin/notes/{id}` returns 403 for non-SG/AD; audit log records the access attempt.

---

#### NT-004: Note audit trail on access [Important]
**Given** a restricted note
**When** a safeguarding lead views it
**Then** an audit log entry is created recording the access

**QA:** Activity log entry created with `event = 'viewed'`, `subject_type = Note`, `subject_id = note_id`, `causer_id = user_id`; includes timestamp.

---

#### NT-005: Note polymorphic attachment [Important]
**Given** a user creating a note
**When** they attach the note to a People record or a Company record
**Then** the note is linked via the `noteables` pivot and appears on the respective record

**QA:** `noteables` pivot stores `note_id`, `noteable_type`, `noteable_id`; note appears in the People resource notes relation manager; note appears in the Company resource notes relation manager.

---

#### NT-006: Note timestamps and author [Critical]
**Given** any note
**When** viewing the note detail or list
**Then** the created_at timestamp and creator name are displayed

**QA:** `created_at` and `creator_id` are auto-populated; Filament columns show `creator.name` and formatted `created_at`.

---

#### NT-007: Note permissions by role [Critical]

| Role | Create Standard | Create Restricted | View Standard | View Restricted | Edit/Delete Own |
|------|----------------|-------------------|--------------|-----------------|----------------|
| FL | Yes | No | Yes | No | Yes (own) |
| AS | Yes | No | Yes | No | Yes (own) |
| CO | Yes | No | Yes | No | Yes (own) |
| SG | Yes | Yes | Yes | Yes | Yes (own + restricted) |
| MG | No | No | Yes | No | No |
| AD | Yes | Yes | Yes | Yes | Yes (all) |

**QA:** Note creation form shows `visibility` field only for SG/AD; NotePolicy enforces `view` and `create` permissions per role; edit/delete limited to own notes unless SG/AD.