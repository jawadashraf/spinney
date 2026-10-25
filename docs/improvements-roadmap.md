# Spinney: improvements roadmap

Date: 25 October 2026

Possible improvements across the whole system, found while reviewing the code, the staging site and the project documents. Nothing here is committed work; it's a list to choose from.

The system currently runs on a demo/staging site, `https://spinney.j-commerce.co.uk`. The live site will be **recoverynetwork.org.uk** (see *Go-live* below).

Where an item needs a decision from Spinney Hill, it says so. Work that is outside the signed proposal (session logs, scheduled forms, payments, classes, chatbot) is listed at the end and tracked in `module-checklist.md`.

**Priority:** **P1** do soon · **P2** worth doing · **P3** nice to have
**Effort:** **S** under a day · **M** a few days · **L** a week or more

---

## Top 10

| # | Improvement | Area | Priority | Effort |
|---|---|---|---|---|
| 1 | ~~Close public account registration~~ Done 25 Oct (needs deploying) | Security | P1 | S |
| 2 | Replace the Relaticle privacy policy and terms with Spinney Hill's own | Compliance | P1 | S |
| 3 | Confirm `APP_DEBUG` is off and that database backups exist and can be restored | Security | P1 | S |
| 4 | Remove the Relaticle sales "Board" (Opportunities) from the menu | Navigation | P1 | S |
| 5 | Two-factor login for managers and admins | Security | P1 | M |
| 6 | Consistent phone number entry and validation everywhere, with readable formatting | Data quality | P1 | M |
| 7 | Calls list: phone numbers, a "Needs notes" tab and badge | Calls | P1 | S |
| 8 | Data quality page: missing or invalid phone numbers, temporary emails, missing consent | Data quality | P2 | M |
| 9 | Fix the failing test suite so problems are caught before deploying | Code health | P2 | L |
| 10 | User acceptance testing against `acceptance/` and client sign-off | Handover | P1 | M |

---

## 1. Security and production (fix first)

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Close public registration.** *Done 25 Oct, needs deploying:* the page and the form post both redirect to login. `https://spinney.j-commerce.co.uk/register` (staging) returned the sign-up page. Staff accounts should only come from invitations or admins. | Anyone can create an account on a system holding service user data. Check what a self-registered account can reach, then remove the route and page. | P1 | S |
| **Confirm `APP_DEBUG=false` in production.** It was found switched on in October. | Debug pages expose code paths, settings and data. | P1 | S |
| **Database backups.** Confirm Hostinger's backup schedule, keep an off-site copy, and test a restore. | The staging database already holds real service user data and there's no documented recovery plan. | P1 | S |
| **Two-factor login.** The login system supports it, but staff aren't required to use it. Require it for managers and admins at least. | These roles can read restricted notes, safeguarding details and donor data. | P1 | M |
| **Remove leftover routes and services:** `/discord`, the sitemap command, GitHub service, API tokens page (if unused), Docker and Horizon files. | Less to maintain, and less exposed. | P2 | S |
| **Error monitoring.** Sentry is installed. Confirm a production DSN is set and that alerts reach someone. | Errors on the site currently go unnoticed unless a user reports them. | P2 | S |
| **Security review of the admin panel** (permissions on every page, record-level access, exports). | Many modules were added quickly; a structured pass catches gaps such as the open registration. | P2 | M |
| **Session timeout** for idle staff sessions. | Shared office computers. | P3 | S |

## 2. Data protection and compliance

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Privacy policy and terms.** Both public pages still contain Relaticle text. Replace them with Spinney Hill's privacy notice: what is collected, the lawful basis for special category data, retention and rights. | Required under UK GDPR. The current text describes another product. | P1 | S |
| **Retention rules.** Only unmatched phone calls are deleted automatically (90 days). Agree retention periods for closed enquiries, old notes, call recordings links, activity logs and leavers' records, then automate them. | Keeping data longer than needed is a compliance risk. *Needs a client decision.* | P2 | M |
| **Subject access request export.** One button to export everything held about a person (record, notes, calls, appointments, donations). | Requests must be answered within a month; doing it by hand is slow and error-prone. | P2 | M |
| **Erase or anonymise a person.** A managed way to remove or anonymise a record while keeping statistics. | Right to erasure. | P3 | M |
| **Call recordings decision.** Spinney stores links only. Decide whether recordings may be kept, for how long, and who may play them. | Open since the phone integration began. *Needs a client decision.* | P2 | S |

## 3. Navigation and general usability

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Remove the Opportunities "Board" from the menu.** It's Relaticle's sales pipeline, and every user can open it. There are also two menu items called "Board" (Opportunities and Tasks). | Confusing, and not part of the charity's work. Rename the Tasks one to "Task board". | P1 | S |
| **Menus per role.** Check what each role sees (liaison, volunteer liaison, counsellor, manager, admin) and hide what they don't use. | Fewer choices for occasional users, especially volunteers. | P2 | M |
| **Global search.** Let the top search bar find people by **phone number** in any format (it searches names only), add enquiries, and check that results respect restricted notes and volunteer access. | Staff look people up constantly, often while on the phone. | P2 | S |
| **Role dashboards.** Liaison and manager dashboards exist; add a counsellor dashboard (today's sessions, sessions needing recording). | The multi-dashboard item in the checklist is only partly done. | P2 | M |
| **Mobile and tablet check.** Test the main liaison and counsellor screens on a phone and fix layouts. | Staff may update records away from a desk. | P3 | M |
| **In-app help.** The knowledge base still has Relaticle text. Replace it with the Spinney guides (appointments, calls, donations, phone system). | Staff look for help inside the app first. | P2 | M |
| **Consistent wording.** One review pass over labels, empty states and notification text, using the same terms throughout (service user, liaison, enquiry). | Some screens still use Relaticle terms. | P3 | S |

## 4. Service users, people and notes

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Consistent phone entry.** Service users use a phone input with a country picker; people, companies, enquiries and donors use a plain text box. Use one validated phone field everywhere, and show numbers readably (`07438 356 414`). | Invalid numbers stop calls being matched (one service user has a 9-digit mobile). | P1 | M |
| **Duplicate warnings for service users.** Warn when a new service user matches an existing person by phone, email, or name and date of birth. Donors already have duplicate warnings. | Duplicates split a person's history across two records. | P2 | M |
| **Data quality page.** Lists of records with missing or invalid phone numbers, temporary email addresses, missing consent, or no assigned liaison, with links to fix them. | Makes clean-up a routine task rather than a surprise. | P2 | M |
| **Service users on the People list,** or one search across both. | Today a service user can't be found from People, which blocks some donation and Gift Aid tasks. See *Open client questions* in `progress-log.md`. | P2 | M |
| **Edit and delete own notes only** (NT-007). | In the acceptance criteria; currently role-based only. | P2 | S |
| **Internal care plans and assessments.** | Only external care plans exist. *Needs a client decision.* | P3 | L |
| **Single timeline per service user** (notes, calls, phone calls, appointments, enquiries in one list). | Partly there; staff currently move between tabs. | P3 | M |

## 5. Calls and the phone system

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Phone numbers in the Calls list.** | Liaisons can work down "Due today" and click to call without opening each call. | P1 | S |
| **"Needs notes" tab and menu badge** for answered calls waiting for notes, plus a nudge in the daily reminder. | Answered calls are only flagged by a small line under the status and are easy to forget. | P1 | S |
| **Phone call summary at the top of the call page:** time answered, talk time, recording, **Add call notes**. | The details are currently in the Attempts section further down. | P2 | S |
| **Missed call alerts.** When a service user rings and nobody answers, notify their liaison to call back. | Missed inbound calls are only logged today. | P2 | S |
| **Unmatched calls grouped by number,** with a count and a suggested match (for example, the number on an existing enquiry). | Repeat callers currently fill the list with one row per call. | P2 | M |
| **Phone calls overview for managers:** every call, filterable by staff member, date, direction and outcome. | No single place to see call activity across the team. | P2 | M |
| **Calls dashboard widget:** calls today, answer rate, awaiting notes. | Quick health check for managers. | P3 | S |
| **Request logging for the phone system,** one log file per day with each request's outcome. | Setup problems took hours to diagnose without it. | P2 | S |
| **Click-to-call button,** if the Linkus extension supports it, rather than hovering over the number. | Easier for less confident users. *Needs checking with Yeastar.* | P3 | S |
| **Supplier questions.** PBX edition, firmware and plan are still unconfirmed. | Confirms the integration is covered by the plan. | P2 | S |

## 6. Appointments

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Shared calendar view** (AP-007, desirable). | Staff and managers can see the week at a glance. Needs a calendar plugin. | P2 | M |
| **Text message reminders** for attendees without email. | Many service users don't read email. Needs an SMS provider. | P3 | M |
| **Waiting list:** offer cancelled slots to people waiting. | Fills cancellations. | P3 | M |

## 7. Enquiries and the website

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Spam protection on the website callback form** (rate limit, honeypot field). Check what's in place now. | A public form that creates enquiries will attract spam. | P2 | S |
| **Enquiry follow-up reminders** for enquiries left open past their due date. | Stops new referrals going cold. | P2 | S |
| **Enquiry source reporting** (website, phone, walk-in, referral). | Shows where referrals come from. | P3 | S |

## 8. Outreach and email

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Production mail setup:** Hostinger SMTP settings, `storage:link` for images, the real daily limit, and a live test send. | Outreach email can't be used live until this is done. | P1 | S |
| **Automatic bounce handling,** or a move to a mail service such as Postmark. | Bounces are marked by hand today. | P3 | M |

## 9. Donations and Gift Aid

Built on 18 October and on hold until the client has reviewed it. The open questions are listed in `progress-log.md` under *Open Client Questions*, for example:
- marking several donations as claimed at once,
- taking a Gift Aid declaration while adding a new donor,
- whether claimed donations should be locked against edits,
- whether the Donors menu should include companies.

## 10. Reporting

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Calls and phone reports:** attempts, answer rates, calls per liaison, average time to complete. | Liaison work is the core service, but has no report beyond the dashboard. | P2 | M |
| **Scheduled reports by email** (monthly summary to managers and trustees). | Saves building the same report by hand each month. | P3 | M |
| **Funder and trustee report pack:** one export with the figures funders usually ask for. | *Needs a client decision on which figures.* | P3 | M |

## 11. Code health

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **Fix the failing tests.** About 123 of 732 tests fail, mostly inherited from Relaticle. In the Filament resource tests, a shared setup step inserts a duplicate team membership (37 failures). The People relationship test misses a team id. Four architecture rules fail on older files. | A reliable test suite is the only safety net when deploying straight to the live server. | P2 | L |
| **Automatic test runs** on every push to `main` (GitHub Actions). | Catches breakage before it's deployed. | P2 | S |
| **Remove unused Relaticle modules and files:** Opportunities, Docker and Horizon config, unused services. | Less code to keep working and upgraded. | P3 | M |
| **Deployment checklist or script:** pull, migrate, clear caches, check the scheduler. | Deployments are manual and easy to get slightly wrong. | P3 | S |

## 12. Go-live on recoverynetwork.org.uk

| Step | Why | Priority | Effort |
|---|---|---|---|
| **Point the domain** and set up HTTPS for the live address. | The system currently runs on the staging address. | P1 | S |
| **Update `APP_URL`** and anything else that builds links from the address (email links, outreach unsubscribe links, phone system screen pops). | Old links would point at the staging site. | P1 | S |
| **Update the phone system:** change the Spinney URL in the Yeastar CRM integration to the live address. | Otherwise calls keep going to staging. | P1 | S |
| **Decide what happens to staging:** keep it as a test site with demo data only, or switch it off. | Staging currently holds real service user data. | P1 | S |
| **Update the documents** that quote the staging address (phone system guide, user guides). | Staff would follow the wrong links. | P2 | S |
| **Production checklist:** debug off, backups, mail settings, cron for the scheduler, storage link. | One pass so nothing is missed on the day. | P1 | S |

## 13. Handover

| Improvement | Why | Priority | Effort |
|---|---|---|---|
| **User acceptance testing** against each file in `acceptance/`, then client sign-off. | Agreed end point of the project. | P1 | M |
| **Training sessions** using the guides (enquiries, calls, appointments, donations, phone system). | Guides alone rarely change habits. | P2 | S |
| **Admin handbook:** adding staff, roles, the phone system, deployments, backups. | So the charity isn't dependent on one person. | P2 | S |

---

## Outside the signed scope (quote separately)

Tracked in `module-checklist.md`:
- Care timeline and session logs (check-in, spiritual, therapy)
- Dynamic scheduled forms for service user updates
- Payments and charging
- Classes and group sessions (plan written: `implementation-plan-classes.md`)
- AI chatbot for enquiries
