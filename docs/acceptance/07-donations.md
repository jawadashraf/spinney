# Donations & Gift Aid Acceptance Criteria

**Module:** Donations & Gift Aid Tracking
**AC IDs:** DN-001 to DN-004
**Requirements ref:** §9.3 — Donors & Supporters (Gift Aid)

---

#### DN-001: Record a donation [Important]
**Given** a fundraising (FR) or admin (AD) user
**When** they create a donation record with donor, amount, date, and Gift Aid status
**Then** the donation is saved and linked to the donor (People or Company)

**QA:** Donation record created with `donor_type`, `donor_id`, `amount`, `donated_at`, `gift_aid_status`; polymorphic relation to People/Company; Gift Aid status is a selectable field.

---

#### DN-002: Gift Aid consent tracking [Important]
**Given** a donor record with Gift Aid consent
**When** viewing the donor profile or donation list
**Then** the Gift Aid status and consent are clearly displayed

**QA:** `gift_aid_status` field on Donation or People model; consent fields visible on donor profile; donations eligible for Gift Aid are filterable.

---

#### DN-003: Donation reporting by period and donor [Important]
**Given** a set of donation records
**When** a manager (MG) or admin (AD) runs a donation report
**Then** donations can be filtered and grouped by date range, donor, and Gift Aid status

**QA:** Filament table filters for `donated_at` date range, `donor_id`, `gift_aid_status`; aggregate totals available; export to CSV/Excel.

---

#### DN-004: Donation permissions by role [Critical]

| Role | Create | View | Update | Delete | Report |
|------|--------|------|--------|--------|--------|
| FR | Yes | Yes (own team) | Yes (own team) | No | Yes |
| MG | No | Yes (all) | No | No | Yes |
| AD | Yes | Yes (all) | Yes | Yes | Yes |

**QA:** Donation policy restricts access per role; FR cannot delete; MG read-only with report access; AD has full access.