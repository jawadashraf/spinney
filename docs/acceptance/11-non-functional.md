# Non-Functional Acceptance Criteria

**Module:** Performance, Security & Browser Compatibility
**AC IDs:** NF-001 to NF-006
**Requirements ref:** Cross-cutting

---

## Performance

#### NF-001: Page load time [Important]
**Given** a standard dataset (≤10,000 records per model)
**When** a user loads any Filament resource list page
**Then** the page renders within 3 seconds

**QA:** Measure with browser DevTools or Lighthouse; average response time < 3s for list pages with pagination.

---

#### NF-002: Enquiry creation speed [Critical]
**Given** a frontline worker
**When** they log a new phone enquiry
**Then** the entire process (open form, fill, submit) takes under 60 seconds

**QA:** UAT criterion from kickoff checklist; form has minimal required fields; auto-fill where possible (caller lookup, timestamp, handler).

---

## Security

#### NF-003: Data isolation between tenants [Critical]
**Given** Team A and Team B with separate data
**When** any user in Team A performs any read or write operation
**Then** they never see or modify Team B's data

**QA:** All tenant-scoped models use global scope filtering by `team_id`; direct URL manipulation returns 404/403; no cross-tenant data leaks in API or Filament responses.

---

#### NF-004: HTTPS enforcement [Important]
**Given** the production environment
**When** a user accesses the application
**Then** all requests are served over HTTPS

**QA:** SSL/TLS certificate installed; HTTP requests redirect to HTTPS; Herd secure site or production server configured.

---

#### NF-005: Session security [Important]
**Given** an authenticated session
**When** the session is inactive for the configured timeout
**Then** the session expires and the user must re-authenticate

**QA:** Session lifetime configured in `config/session.php`; Filament auth session respects timeout; idle sessions expire.

---

## Browser Compatibility

#### NF-006: Browser support [Important]
**Given** a user accessing the CRM
**When** they use Chrome, Edge, Safari, or Firefox (latest 2 versions)
**Then** all features function correctly

**QA:** UAT testing on Chrome, Edge, Safari, Firefox latest; no JavaScript errors; all Filament components render correctly.