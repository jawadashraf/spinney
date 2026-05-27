---
type: documentation
---

# Enquiries & Service Users — User Guide

This guide covers working with Enquiries, Service Users, Notes, and External Care Plans. The admin panel is at **[spinney.j-commerce.co.uk/admin](https://spinney.j-commerce.co.uk/admin)**.

---

## 1. Enquiries

Enquiries track every incoming and outgoing contact — phone calls, walk-ins, emails, online messages, and referrals. They live under **Liaison → Enquiries** in the sidebar.

### Viewing the Enquiry List

The list shows all enquiries sorted by date (most recent first). Each row shows:

- **Direction** (Inbound / Outbound) and **Call Type** as badges
- **Category** and **Caller name** (or "Anonymous")
- A preview of the **reason for contact**
- **Safeguarding** icon (red warning triangle if flagged)
- **Staff member** assigned, **Department** badge, **Status** badge
- **Date** the enquiry occurred

**Filter the list** using the filter button at the top right — you can narrow by category, status, direction, call type, source, department, staff member, safeguarding flags, or a date range.

### Creating an Enquiry

Click **"New Enquiry"** at the top right. You'll go through a 4-step wizard:

**Step 1 — Caller Identity**
- Choose **Known Caller** (someone already in the system), **New Caller** (add them on the spot), or **Anonymous / Withheld**
- For known/new callers, type to search for or create the person
- Phone number is optional

**Step 2 — Enquiry Details**
- Direction: select **Inbound** (they called us) or **Outbound** (we're calling them)
- Call Type: General, Follow-up, Check-in, Scheduled, or Emergency
- Source: Phone, Walk-in, Email, Online, or Referral
- Category: pick the most relevant option
- Briefly describe the **reason for contact**
- Set the date/time it happened (defaults to now)

**Step 3 — Assignment & Safeguarding**
- Assign a **Department** (optional) and a **Staff Member** (defaults to you)
- For outbound enquiries, set a **Due Date**
- Toggle **Safeguarding Flags** on if there are any safeguarding concerns, then describe the **Risk Flags**

**Step 4 — Actions & Outcomes**
- Record any **Advice Given** or **Action Taken**
- Add a **Referral** (internal or external) and specify the destination

Click **Create** and you're done.

### Viewing an Enquiry

Click any enquiry in the list to open the detail view. It's organised into sections:

- **Enquiry Overview** — direction, call type, source, caller info, category, status
- **Assignment & Follow-up** — department, due date, any linked follow-ups
- **Safeguarding & Risk** — safeguarding flag and risk notes
- **Narrative** — reason for contact, advice given, action taken
- **Referral** — referral type and destination (if any)
- **Staff & Timeline** — who handled it and when it was logged

From the detail view, you can also **Edit**, **Close**, **Promote to Service User**, **Create a Follow-up**, **Assign to Department**, or **Link to a Person** using the buttons at the top.

### Editing an Enquiry

Click the **Edit** button (from the list or the detail view). The form has the same fields as the wizard, shown in sections rather than steps. Make your changes and save.

### Closing an Enquiry

When an enquiry is resolved:

1. Open the enquiry
2. Click **Close Enquiry** in the header
3. Optionally set an **Outcome** (for outbound calls — Answered, No Answer, Voicemail, Callback Required)
4. Add any **Closure Notes**
5. Confirm

The status changes to **Closed**.

### Promoting an Enquiry to Service User

If a caller needs ongoing support, you can convert their enquiry into a full Service User record:

1. Open an enquiry with status **Open** that has a linked caller
2. Click **Promote to Service User**
3. Fill in their profile details (see the Service User section below)
4. Submit — this creates their login account, updates their profile, and marks the enquiry as **Converted**

All admin users will be notified.

### Creating a Follow-up Enquiry

From any enquiry, click **Create Follow-up** to log a new outbound call linked to the original. The caller info and category are carried over automatically.

---

## 2. Service Users

Service Users are the people you support. They're listed under **Service Users → Service Users** in the sidebar.

### Creating a Service User

Click **"New Service User"** at the top right. The form has two main parts:

**Identity** (top section)
- **Name** (required) and **Email** (required — used for their login)
- Leave **Password** blank to auto-generate one

**Service User Details** (organised in tabs):

**Demographics & Consent**
- Date of birth, gender, ethnicity
- Phone, postcode, address (toggle "No current fixed address" if needed)
- Availability (e.g. "Weekdays after 5pm")
- Emergency contact name and number
- **Consent toggles** — Data Storage is required; Referrals and Communications are optional

**Assessment**
- Addictions (smoking, drugs, gambling, etc.)
- Substances used (heroin, cocaine, marijuana, alcohol, etc.)
- Frequency, amount, and route of use
- Age first used, whether they've overdosed in the last month, injection history
- GP details — toggle "Registered with GP" to fill in name and address

**Referral**
- Referral type (Self, Agency, Family, Police, Other)
- Previous input (GP, Drug Agency, etc.)
- Other issues (Criminal Justice, Housing, Family, Finance, Health)
- Reason for referral

**Service Plan**
- Service Team (Assessment, Drug & Alcohol, Spiritual, Education & Outreach, Aftercare)
- Engagement Status (Pending, Active, Inactive, Discharged)
- Next steps (referrals and interventions offered)
- Treatment outcome and internal notes

Click **Create** to save.

### Editing a Service User

Open a service user and click **Edit**. The form is the same as Create, pre-populated with their data. Changes to the email address will automatically update their login account too.

---

## 3. Service User Notes

Notes let you record free-form case notes, observations, and updates on a service user.

### Finding Notes

1. Go to **Service Users → Service Users** and click a name
2. Click **Edit**
3. Switch to the **Notes** tab

### Adding a Note

1. Click **"New Note"**
2. Enter a **Title** and the **Body** (rich text editor with @mentions — type `@` to tag a colleague)
3. Save

The note appears in the timeline below, grouped by date.

### Viewing Notes

Notes are displayed as a timeline, newest first. Each entry shows:

- The **title** and who wrote it
- **How long ago** it was written
- The **body** (expand/collapse for long entries)

Notes are internal — visible only to staff members.

---

## 4. External Care Plans

External Care Plans track third-party care providers involved with a service user — referrals to Turning Point, ADS, or other agencies.

You can manage them from two places:

- **Service Users → Service Users** → Edit a user → **External Care** tab
- **Service Users → External Care Plans** in the sidebar

### Creating a Care Plan

1. Click **"New External Care Plan"**
2. Fill in:

**Provider Information**
- **Provider Name** — select Turning Point, ADS, or "Other" (then type the name)
- **Provider Contact** — email, phone, and address

**Service User**
- **Service User** — search and select (auto-fills their email and phone)

**Status & Dates**
- **Status** — starts as Pending
- **Referral Date** (required, can't be in the future)
- **Start Date** — appears when status is In Progress or Completed
- **End Date** — appears when status is Completed
- **Support Managers** — assign staff members who oversee this plan

**Notes**
- **Notes** — visible to the service user if shared
- **Internal Notes** — visible only to staff

**Attachments** (optional)
- Upload files related to the care plan (clinical documents, correspondence, legal forms, etc.)

Click **Create** to save.

### Viewing & Editing a Care Plan

Open a care plan from the list (or the service user's External Care tab). The detail view shows all the information at a glance.

Click **Edit** to update. Note that once a plan is marked **Completed**, changes are locked. You can always add more attachments though.

### Attachments

Each attachment can have:
- A **Name** (descriptive label)
- A **Category** (Clinical, Correspondence, Legal, or Other)
- **Tags** for easy searching

To view or download, use the **Open** or **Download** action on the attachment.

---
