# Spinney phone system guide

How the Yeastar phone system works with Spinney: setting it up, connecting each liaison, making and receiving calls, and what happens to every call afterwards.

> Menu names on the Yeastar side can differ slightly between firmware versions. If a label here doesn't match exactly, look for the nearest equivalent.

---

## Contents

1. [How it fits together](#1-how-it-fits-together)
2. [Quick reference](#2-quick-reference)
3. [One-time system setup (administrator)](#3-one-time-system-setup-administrator)
4. [Adding a liaison: map their extension (administrator)](#4-adding-a-liaison-map-their-extension-administrator)
5. [Setting up a liaison's computer (each liaison)](#5-setting-up-a-liaisons-computer-each-liaison)
6. [Making a call from Spinney](#6-making-a-call-from-spinney)
7. [What happens after a call](#7-what-happens-after-a-call)
8. [Adding notes to an answered call](#8-adding-notes-to-an-answered-call)
9. [Receiving calls](#9-receiving-calls)
10. [Phone calls tab](#10-phone-calls-tab)
11. [Unmatched calls](#11-unmatched-calls)
12. [Recordings](#12-recordings)
13. [Troubleshooting](#13-troubleshooting)
14. [Technical reference (administrator)](#14-technical-reference-administrator)

---

## 1. How it fits together

- **Yeastar (the phone system)** makes and receives calls and records them.
- **Spinney** holds service users, relatives, professionals, donors and companies.
- The **Spinney integration** on Yeastar connects the two:
  - When a call rings, Yeastar asks Spinney who the number belongs to, so the caller's name can be shown and their Spinney page opened.
  - When a call ends, Yeastar sends Spinney the call details: who called whom, whether it was answered, how long it lasted, and a link to the recording.
- **Linkus** is the Yeastar app used to make calls from a computer. The **Linkus for Chrome** extension adds a call button next to phone numbers in Spinney.

**Important:** Yeastar only sends calls to Spinney for extensions that are **mapped to a Spinney user** (see [section 4](#4-adding-a-liaison-map-their-extension-administrator)). Calls from an unmapped extension are not logged in Spinney at all.

The phone system never creates or changes records in Spinney. It only reads them and logs calls against them.

---

## 2. Quick reference

| I want to… | Where |
|---|---|
| Call a service user | Open their due call, their record, or the Service Users list, hover over the number, click the call icon |
| See calls due today | **Liaison → Calls → Due today** |
| Add notes after an answered call | **Liaison → Calls**: calls marked **"Answered – add notes"** → **Add call notes** |
| See someone's call history | Their record → **Phone calls** tab |
| Deal with calls from unknown numbers | **Liaison → Unmatched calls** |
| Listen to a recording | **Play** in the Phone calls tab or Unmatched calls, or **Play recording** in a call's Attempts section |
| Give someone phone system access | Spinney role (section 4) + extension mapping on Yeastar (section 4) |

---

## 3. One-time system setup (administrator)

This is already done. It's listed here for reference and in case the integration ever needs setting up again.

### 3.1 Spinney server

In the production `.env` file:

```
YEASTAR_API_KEY=<long random key, kept secret>
YEASTAR_TEAM_ID=<the id of the Spinney Hill team>
YEASTAR_MISDIAL_SECONDS=10
YEASTAR_UNMATCHED_RETENTION_DAYS=90
```

- `YEASTAR_MISDIAL_SECONDS`: unanswered outbound calls shorter than this aren't counted as call attempts.
- `YEASTAR_UNMATCHED_RETENTION_DAYS`: calls from unknown numbers that nobody links are deleted after this many days.

After any change, run `php artisan optimize:clear` on the server.

### 3.2 Yeastar

1. Go to **Integrations → CRM** and upload the template `Spinney.xml` (version 1.2.0, in `docs/yeaster call setup/`).
2. Enter the connection details:
   - **Spinney URL:** `https://spinney.j-commerce.co.uk`
   - **API Key:** the same key as `YEASTAR_API_KEY`
3. Save. The integration should show as connected.
4. In the integration settings, make sure these are switched on:
   - **Call journal** (logs calls to Spinney)
   - **Automatic contact creation**, with contact type **contacts**, for **inbound and outbound** calls. Spinney doesn't create anyone; this setting only lets Yeastar log calls from unknown numbers (see [section 11](#11-unmatched-calls)).
   - **Call recording playback** (sends the recording link)

---

## 4. Adding a liaison: map their extension (administrator)

Do this for every member of staff who will make or take calls. **Without the mapping, their calls are not logged in Spinney.**

### 4.1 In Spinney: give them a call role

Only Spinney users with one of these roles appear in the phone system's user list:

- Liaison
- Volunteer liaison
- Manager
- Admin

To check or change a role, go to **Workspace → Users**, open the person, and set **Roles**.

### 4.2 In Yeastar: map the extension

1. Go to **Integrations → CRM** and open the **Spinney** integration.
2. Open the **User Association** section.
3. If there is a sync or refresh button for users, click it so the latest Spinney users are fetched.
4. Find the person's **extension** (for example, *1000 – Jawad Ashraf*) and select their **Spinney user** in the CRM user column.
5. **Save.**

Yeastar may also offer **automatic association by email**. This works when the extension's email in Yeastar is the same as the person's Spinney login email.

### 4.3 In Yeastar: allow Linkus

Make sure the extension is allowed to use the **Linkus Web Client** (and the Linkus Desktop app if you use it), and give the liaison their Linkus login details.

### 4.4 Check it works

Ask the liaison to call a mobile phone from their extension and answer it. Within a minute the call should appear in Spinney, either on the contact's **Phone calls** tab or under **Liaison → Unmatched calls**.

---

## 5. Setting up a liaison's computer (each liaison)

You need Google Chrome (version 87 or later) and your Linkus login details from the administrator.

1. **Install the extension:** search the Chrome Web Store for **"Yeastar Linkus for Google"** and click **Add to Chrome**.
2. **Connect it to Linkus:** click the **Yeastar icon** in the Chrome toolbar and choose **Linkus Web Client**.
3. **Log in** to the Linkus Web Client with your Linkus details. If it was already open, refresh it. The Yeastar icon changes when the extension is ready.
4. **Optional:** right-click the Yeastar icon, choose **Options**, and switch on **"Dial out immediately on clicking phone number"**. Without it, clicking a number only puts it into the Linkus dial pad and you press call yourself.
5. Use a **headset** plugged into the computer.

**Every working day:** keep the **Linkus Web Client tab open and logged in** while you make calls. Calls go through it.

---

## 6. Making a call from Spinney

Phone numbers you can click to call are shown in:

| Page | Where the number is |
|---|---|
| A call (Liaison → Calls → open a call) | **Phone**, next to the service user's name |
| A service user's record (view or edit) | **"Phone: …"** under their name, at the top of the page |
| Service Users list | **Phone** column |
| People list | **Phone** column |
| Companies list and a company's page | **Phone** |

**To call:**

1. **Hover** the mouse over the phone number. A call icon appears.
2. Click it. Linkus dials the number.
3. Talk as normal. When you hang up, Spinney is updated automatically within about a minute (see the next section).

**Notes:**
- Numbers inside **form fields** (for example, the phone box when editing a record) can't be clicked. Use the "Phone: …" line at the top of the page instead.
- If no icon appears, reload the page (**F5** or **Cmd+R**) and check that the Linkus Web Client is open and logged in.
- A number must be a complete, valid number. A UK mobile has 11 digits starting **07**, or **+44 7** followed by 9 more.

**Working through your calls:** go to **Liaison → Calls → Due today** (or **Overdue**), open a call, hover over the number and click to call. Afterwards, add your notes on the same page.

---

## 7. What happens after a call

When a call ends, Spinney works out who it was with (by the number) and records it.

### 7.1 Calls with a service user who has a call due

Spinney matches the phone call to the service user's **open call that is due today or overdue**, preferring one assigned to you.

| What happened | What Spinney does | What you see |
|---|---|---|
| **You called and they answered** | Adds an **Answered** attempt with the time, talk time and recording. The call stays open for your notes. | The call shows **"Answered – add notes"**. The button reads **Add call notes**. |
| **They called you and you answered** | Same as above | Same as above |
| **You called and they didn't answer** (rang 10 seconds or more), or were busy | Records a **No answer** attempt and moves the call to **the next working day**. After the last allowed attempt, the call is marked **Missed**, managers are notified, and the next regular call is booked. | Attempt count goes up. The attempt note reads *"Logged by the phone system: no answer."* |
| **You called and it went to voicemail** | Records a **Voicemail** attempt, as above | Outcome **Voicemail left** |
| **You hung up within 10 seconds** without an answer | Treated as a misdial: **logged only**, no attempt counted | Appears on their **Phone calls** tab only |
| **They called you and you missed it** | **Logged only**. Their scheduled call is not changed. | **Phone calls** tab |
| **The call was already answered and waiting for notes** | The new call is **logged only**, so no second attempt is added | **Phone calls** tab |

### 7.2 Calls with a service user who has no call due today

The call is **logged only**, on their **Phone calls** tab. A call due later in the week is **not** completed early.

### 7.3 Calls with a relative, professional, donor or company

The call is **logged only**, on that person's or company's **Phone calls** tab. These calls never change a service user's liaison call.

### 7.4 Calls with numbers that aren't in Spinney

The call appears under **Liaison → Unmatched calls** (see [section 11](#11-unmatched-calls)).

### 7.5 Shared numbers

If several records have the same number (for example, a service user and a relative sharing a landline), the **service user** comes first, then other people, then companies.

---

## 8. Adding notes to an answered call

1. Go to **Liaison → Calls**. Answered calls waiting for notes show **"Answered – add notes"** under the status.
2. Open the call and click **Add call notes**. The window shows when the call was answered on the phone system.
3. **Outcome** is already set to **Answered**. Change it only if the call wasn't really a conversation, for example if someone else picked up.
4. Fill in:
   - **Call notes** (required)
   - **Action required** (optional)
   - **Next follow-up call**: pre-filled from the call plan. It's required while the plan continues. To stop regular calls, ask a manager to pause the plan.
   - **Raise support / safeguarding concern** if needed, and choose the **concern level**
5. Click **Save outcome**. The call is completed, the note is added to the service user's record, and the next call is booked.

The attempt recorded by the phone system is completed with your notes; no second attempt is added.

---

## 9. Receiving calls

When someone rings your extension and Linkus is open:

- **Known number:** Linkus shows their name, and can open their Spinney page:
  - **Service user with a call due today:** that **call** opens, ready for notes.
  - **Service user with no call due:** their **service user record**.
  - **Relative, professional or donor:** their **People** record.
  - **Company:** the **company** page.
- **Unknown number:** no name is shown. After the call, it appears under **Unmatched calls**.

---

## 10. Phone calls tab

Every **service user**, **person** (relative, professional, donor) and **company** record has a **Phone calls** tab listing every call the phone system logged with them.

| Column | Meaning |
|---|---|
| When | Date and time the call started |
| Direction | **Inbound** (they called) or **Outbound** (we called) |
| Staff | Who handled it. *Unmapped extension* means the extension isn't mapped to a Spinney user. |
| Outcome | Answered, No answer, Busy, Voicemail, Missed |
| Talk time | Minutes and seconds |
| Recording | **Play** downloads the recording |
| Liaison call (service users only) | What the call did to their liaison call: **Answered – awaiting notes**, **Attempt recorded** or **Logged only**. Click it to open the call. |

The tab is read-only. Phone call logs can't be edited or deleted here.

---

## 11. Unmatched calls

**Liaison → Unmatched calls** lists calls with numbers that aren't on anyone's record: new callers, referrals, wrong numbers and spam. The menu shows how many are waiting.

- **Managers and admins** see every unmatched call.
- **Other staff** see only the calls they handled.

### What you can do with each call

| Action | What it does |
|---|---|
| **Link to contact** | Search for the person (any type) or company. **Every** unmatched call from that number moves to their **Phone calls** tab. Leave **"Save this number on their record…"** on to add the number to a record that has none, so their future calls are recognised automatically. Companies can only be chosen by managers and admins. |
| **Start enquiry** (in the **…** menu) | Opens a new enquiry with the **phone number, time and direction filled in** and **New caller** selected. When you save the enquiry, the call is linked to it and leaves the list. |
| **Dismiss** (in the **…** menu) | For spam or wrong numbers. Removes the call log from Spinney; the phone system keeps its own record. Tick several calls and use **Dismiss selected** to clear many at once. |

### Automatic tidying

- **Automatic linking:** when a number is added to a person or company in Spinney (for example, when a new service user is registered), their earlier unmatched calls move onto their **Phone calls** tab.
- **Automatic deletion:** calls left unlinked are **deleted after 90 days**. Calls linked to a person, company or enquiry are kept.

Past calls are **never** used to complete a liaison call. Linking only adds them to the call history.

---

## 12. Recordings

- Calls are recorded by the phone system. Spinney stores **only a link** to each recording, not the recording itself, pending a data protection decision.
- Click **Play** (Phone calls tab, Unmatched calls) or **Play recording** (a call's **Attempts** section) to **download** the recording and play it on your computer.
- Treat recordings as personal data: don't forward or keep copies, and delete downloaded files when you've finished with them.

---

## 13. Troubleshooting

| Problem | Likely cause | What to do |
|---|---|---|
| A call doesn't appear anywhere in Spinney | The extension isn't mapped to a Spinney user | Map it under **User Association** ([section 4.2](#42-in-yeastar-map-the-extension)) |
| | Call journal is switched off | Switch it on in the Spinney integration on Yeastar |
| | It's only been a few seconds | Yeastar sends the call after it ends; wait a minute and refresh |
| A member of staff isn't in the User Association list | They don't have a call role in Spinney | Give them Liaison, Volunteer liaison, Manager or Admin ([section 4.1](#41-in-spinney-give-them-a-call-role)), then sync users on Yeastar |
| A known person's call went to Unmatched calls | Their number is missing or different on their record | Use **Link to contact** with "Save this number" on, or correct the number on their record |
| No call icon next to a number | The number is in a form field | Use the "Phone: …" line at the top of the page |
| | Spinney loaded the page without a full refresh | Reload the page (**F5** or **Cmd+R**) |
| | Linkus Web Client isn't open or logged in | Open and log in to the Linkus Web Client |
| | The number is incomplete or invalid | Correct the number on the record |
| The icon only shows when hovering | Normal | Hover, then click |
| An answered call didn't update the service user's due call | The call isn't due until a later day | Expected; the call is only on their Phone calls tab |
| | The due call is assigned to another liaison you can't see | Ask a manager to reassign it, or record it manually |
| | The call was already waiting for notes | Add notes to the existing answered attempt |
| An unanswered call didn't count as an attempt | It rang for less than 10 seconds | Expected; treated as a misdial |
| The screen pop opened the record, not the call | No call is due today for that service user | Expected |
| Play downloads a file instead of playing it | That's how the phone system serves recordings | Open the downloaded file |

---

## 14. Technical reference (administrator)

### Endpoints called by Yeastar

All endpoints are under `https://spinney.j-commerce.co.uk/api/yeastar/` and use the API key as basic authentication.

| Request | Purpose |
|---|---|
| `GET users` | Spinney users for **User Association** (users with a call role) |
| `GET contacts?phone=…` | Look up who a number belongs to. Yeastar's connection test looks up `phone=999`. |
| `GET contacts/search?q=…` | Search contacts by part of a name or number |
| `POST contacts` | Yeastar's "create contact" for unknown numbers. **Creates nothing**; returns an `unknown-…` id so the call can be logged. |
| `POST call-journal` | A finished call |
| `PUT call-journal/{id}` | Later recording link for a logged call |

### Checking whether calls arrive

On the server, in the app folder, list the latest logged calls:

```bash
php artisan tinker --execute 'App\Models\PbxCallLog::latest("id")->take(5)->get(["id","created_at","direction","disposition","user_id","people_id","company_id","contact_number","result"])->each(fn ($l) => print(json_encode($l->toArray()).PHP_EOL));'
```

In **hPanel → Websites → Dashboard → Analytics → Access logs**, Yeastar's requests show the user agent `Go-http-client`:

| Status | Meaning |
|---|---|
| 200 or 201 | Accepted |
| 401 | API key or team id in `.env` doesn't match; run `php artisan optimize:clear` after changing `.env` |
| 422 | Spinney rejected the call data; report it with the time of the call |
| No requests during a call | Yeastar isn't sending for that extension; check User Association and Call journal |

### Scheduled clean-up

Unmatched calls older than `YEASTAR_UNMATCHED_RETENTION_DAYS` (90) are deleted every night at 03:00 UK time by the Laravel scheduler, which runs from the hosting cron (`php artisan schedule:run`).
