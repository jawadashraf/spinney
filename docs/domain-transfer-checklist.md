# Domain Migration Checklist: Hostinger (Demo to Production)

**Source (Demo):** `https://spinney.j-commerce.co.uk` (Hostinger / MySQL)  
**Destination (Production):** `https://recoverynetwork.org.uk` (Hostinger / MySQL)  
**Application:** Relaticle CRM / Spinney Recovery Centre  
**Stack:** Laravel 13, Filament v5, Livewire 4, MySQL (InnoDB / utf8mb4), Hostinger hPanel

---

## 1. Hostinger Environment Preparation

- [ ] **Configure Domain & DNS in Hostinger hPanel**:
  - [ ] Add `recoverynetwork.org.uk` as a website in Hostinger hPanel (or select it if already added).
  - [ ] Verify DNS A records point to the Hostinger server IP (or Hostinger nameservers `ns1.dns-parking.com` / `ns2.dns-parking.com` or Cloudflare).
  - [ ] Set TTL to `300` seconds on DNS records before cutover.
- [ ] **Activate Hostinger SSL**:
  - [ ] In hPanel -> **Security** -> **SSL**, install the free Lifetime SSL certificate for `recoverynetwork.org.uk` and `www.recoverynetwork.org.uk`.
  - [ ] Enable **Force HTTPS**.
- [ ] **Configure PHP Version & Extensions in hPanel**:
  - [ ] Go to **Advanced** -> **PHP Configuration**:
    - **PHP Version:** Select **PHP 8.4** (Codebase requires `^8.4`).
    - **PHP Extensions:** Ensure `pdo_mysql`, `mysqli`, `fileinfo`, `gd`, `intl`, `bcmath`, `curl`, `zip`, `sodium`, and `opcache` are enabled.
    - **PHP Options:** Increase limits:
      - `memory_limit`: `512M`
      - `upload_max_filesize`: `64M`
      - `post_max_size`: `64M`
      - `max_execution_time`: `300`
- [ ] **Set Up Document Root in Hostinger**:
  - *Laravel's public entry point is the `/public` directory, not `public_html`.*
  - [ ] In hPanel -> **Websites** -> **Manage** -> **General** / **Advanced** -> **Document Root**:
    - Change Document Root from `public_html` to `public_html/public` (or place Laravel files in the domain directory and point Document Root to `public`).
    - *Alternative (if Document Root cannot be changed on your plan):* Place all Laravel files in a folder above `public_html` (e.g. `laravel_app/`) and copy/symlink contents of `public/` into `public_html/`, adjusting `index.php` paths.
- [ ] **Create Destination MySQL Database in hPanel**:
  - [ ] In hPanel -> **Databases** -> **MySQL Databases**:
    - Create a new MySQL database name (e.g. `uXXXXX_recovery_prod`).
    - Create a new MySQL username (e.g. `uXXXXX_dbuser`) and strong password.
    - Note the exact database name, username, password, and host (`localhost` or `127.0.0.1`).

---

## 2. Codebase & Branding Adjustments

Review and update any hardcoded references before running final builds:

- [ ] **Horizon / Access Gates**:
  - File: [`app/Providers/HorizonServiceProvider.php`](file:///Users/jawadashraf/Herd/spinney/app/Providers/HorizonServiceProvider.php#L34)
  - Update `Gate::define('viewHorizon', ...)` which currently restricts access to `manuk.minasyan1@gmail.com`.
- [ ] **Public Enquiries Form Note**:
  - File: [`app/Livewire/PublicEnquiryForm.php`](file:///Users/jawadashraf/Herd/spinney/app/Livewire/PublicEnquiryForm.php#L84)
  - Change `"Online enquiry via recoverynetwork.com."` to `"Online enquiry via recoverynetwork.org.uk."`.
- [ ] **Contact & Support Email Addresses**:
  - Replace instances of `info@spinneyhill.org.uk` with `info@recoverynetwork.org.uk`:
    - [`resources/views/home/landing.blade.php`](file:///Users/jawadashraf/Herd/spinney/resources/views/home/landing.blade.php#L737)
    - [`resources/views/home/partials/site-footer.blade.php`](file:///Users/jawadashraf/Herd/spinney/resources/views/home/partials/site-footer.blade.php#L44)
    - [`resources/views/donate/show.blade.php`](file:///Users/jawadashraf/Herd/spinney/resources/views/donate/show.blade.php#L45)
    - [`resources/views/donate/thank-you.blade.php`](file:///Users/jawadashraf/Herd/spinney/resources/views/donate/thank-you.blade.php#L16)
    - [`resources/views/livewire/public-donation-form.blade.php`](file:///Users/jawadashraf/Herd/spinney/resources/views/livewire/public-donation-form.blade.php#L162)

---

## 3. External Integrations & Webhooks (Critical)

- [ ] **Stripe (Donations & Checkout)**:
  - [ ] In Stripe Dashboard, switch from Test to Live mode (or update production API keys).
  - [ ] Add new Webhook Endpoint:
    - **Endpoint URL:** `https://recoverynetwork.org.uk/stripe/webhook`
    - **Events:** `checkout.session.completed`, `customer.subscription.deleted`, `customer.subscription.updated`
  - [ ] Copy the signing secret (`whsec_...`) and update `STRIPE_WEBHOOK_SECRET` in `.env`.
  - [ ] Set `STRIPE_SECRET` to `sk_live_...` in `.env`.
  - [ ] Set `STRIPE_DONATIONS_TEAM_ID` to the primary charity team ID.
  - [ ] Disable/remove the demo webhook (`https://spinney.j-commerce.co.uk/stripe/webhook`) in Stripe.
- [ ] **Yeastar P-Series PBX Phone System**:
  - [ ] In Yeastar PBX Console -> **Integrations** -> **CRM Integration**:
    - Update **Spinney URL** (`spinney_url` parameter) to `https://recoverynetwork.org.uk`.
    - Ensure `api_key` matches `YEASTAR_API_KEY` in production `.env`.
  - [ ] Set `YEASTAR_TEAM_ID` in `.env` to the production liaison team ID.
- [ ] **Google / GitHub OAuth (Socialite)**:
  - [ ] In Google Cloud Console, add `https://recoverynetwork.org.uk` to Authorized Origins and `https://recoverynetwork.org.uk/auth/callback/google` to Authorized Redirect URIs.
- [ ] **Email Delivery**:
  - [ ] If using Hostinger Business Email (SMTP) or Postmark/SES, update `.env`:
    - `MAIL_MAILER=smtp` (or `postmark`)
    - `MAIL_HOST=smtp.hostinger.com` (port 465 SSL or 587 TLS)
    - `MAIL_FROM_ADDRESS=info@recoverynetwork.org.uk`
    - `MAIL_FROM_NAME="The Recovery Network"`
  - [ ] Ensure SPF, DKIM, and DMARC records for `recoverynetwork.org.uk` are active in DNS.
- [ ] **Address Lookup & Analytics**:
  - [ ] Ideal Postcodes: Whitelist `recoverynetwork.org.uk` if HTTP referrer restriction is active.
  - [ ] Sentry / Fathom Analytics: Update DSN and Site ID.

---

## 4. Production `.env` on Hostinger

Create/edit `.env` on the destination website. Key values for Hostinger + MySQL:

```dotenv
APP_NAME="The Recovery Network"
APP_ENV=production
APP_KEY=base64:... # Keep the EXACT key from demo if transferring encrypted data/sessions
APP_DEBUG=false
APP_URL=https://recoverynetwork.org.uk

SYSADMIN_PATH=sysadmin

# Database Settings for Hostinger MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uXXXXX_recovery_prod
DB_USERNAME=uXXXXX_dbuser
DB_PASSWORD=your_strong_mysql_password

# Session & Cookies
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

# Storage & Uploads
FILESYSTEM_DISK=public
MEDIA_DISK=public

# Queues & Cache
# On Hostinger Shared/Cloud hosting: use 'database' queue unless running VPS with Redis
QUEUE_CONNECTION=database
CACHE_STORE=file

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=info@recoverynetwork.org.uk
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="info@recoverynetwork.org.uk"
MAIL_FROM_NAME="${APP_NAME}"

# Third Party Services
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_DONATIONS_TEAM_ID=1
STRIPE_FEE_PERCENT=1.2
STRIPE_FEE_FIXED_PENCE=20

YEASTAR_API_KEY=your_yeastar_secret_key
YEASTAR_TEAM_ID=1

TEMP_EMAIL_DOMAIN=recoverynetwork.local
```

> [!NOTE]
> **Queue Connection on Hostinger:** On Hostinger Shared or Cloud plans, background daemon processes like `php artisan horizon` cannot run continuously because supervisor is not available. The application already includes an automated fallback in `bootstrap/app.php`:
> ```php
> $schedule->command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping(5);
> ```
> Setting `QUEUE_CONNECTION=database` allows Hostinger's standard cron job to process all background jobs cleanly without needing Redis or Supervisor! (If you are on a **Hostinger VPS**, you can run Redis and Horizon daemon instead).

---

## 5. Cutover & Data Migration Steps

Follow these steps during the maintenance window:

### Step 1: Put Demo Site into Maintenance
```bash
# Via SSH on demo site:
php artisan down --secret="cutover-2026"
```

### Step 2: Export MySQL Database from Demo
- **Option A (Via phpMyAdmin in hPanel):**
  1. Open Hostinger hPanel for `spinney.j-commerce.co.uk` -> **Databases** -> **phpMyAdmin**.
  2. Select the demo database.
  3. Click **Export** -> Format: **SQL** -> Compression: **gzipped** -> Click **Export**.
- **Option B (Via SSH):**
  ```bash
  mysqldump -u uXXXXX_demouser -p uXXXXX_demodb --single-transaction --quick > /home/uXXXXX/demo_db_backup.sql
  ```

### Step 3: Import MySQL Database into Production
- **Option A (Via phpMyAdmin in hPanel):**
  1. Open Hostinger hPanel for `recoverynetwork.org.uk` -> **Databases** -> **phpMyAdmin**.
  2. Select the empty production database (`uXXXXX_recovery_prod`).
  3. Click **Import** -> Choose the exported SQL file -> Click **Import**.
- **Option B (Via SSH):**
  ```bash
  mysql -u uXXXXX_dbuser -p uXXXXX_recovery_prod < /path/to/demo_db_backup.sql
  ```

### Step 4: Transfer Media Files (`storage/app/public`)
Since both sites are on Hostinger, you can move uploaded media (avatars, attachments, documents) quickly via Hostinger File Manager:
1. On Demo site File Manager: Go to `storage/app/public`, select all files/folders, and click **Compress** (create `media_backup.zip`).
2. Download or copy `media_backup.zip` to the destination website's `storage/app/public/`.
3. In destination File Manager: **Extract** `media_backup.zip` into `storage/app/public/`.

### Step 5: Deploy Code & Run Artisan Commands (via SSH)
Connect to the destination server via SSH (details in hPanel -> **Advanced** -> **SSH Access**):

```bash
cd /home/uXXXXX/domains/recoverynetwork.org.uk/public_html  # or your Laravel root folder

# Install production PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Build assets (if not built locally or in CI)
npm ci
npm run build

# Run database migrations to ensure all tables are updated
php artisan migrate --force

# Create the public storage symlink
php artisan storage:link

# Set correct permissions
chmod -R 775 storage bootstrap/cache
```

*(Note: If Hostinger's default CLI PHP is older than 8.4, prepend the PHP 8.4 binary path, e.g. `/usr/bin/php8.4 artisan ...`)*

### Step 6: Configure Cron Job in Hostinger hPanel
In hPanel -> **Advanced** -> **Cron Jobs**:
- **Type:** Custom
- **Command:**
  ```bash
  /usr/bin/php8.4 /home/uXXXXX/domains/recoverynetwork.org.uk/public_html/artisan schedule:run >> /dev/null 2>&1
  ```
- **Interval:** Every Minute (`* * * * *`)

This single cron job will handle:
- Appointments, calls, and task reminders (07:45, 08:00, 09:00 Europe/London).
- PBX unmatched call pruning (03:00).
- Daily sitemap generation (`app:generate-sitemap`).
- Queue processing (`queue:work --stop-when-empty --max-time=50`).

### Step 7: Warm Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components
php artisan app:generate-sitemap
```

---

## 6. End-to-End Testing & Verification

- [ ] **Application Health:** Visit `https://recoverynetwork.org.uk/up` (returns 200 OK).
- [ ] **Authentication & Panels:**
  - Login at `https://recoverynetwork.org.uk/login`.
  - Verify redirection to tenant dashboard (`/{tenant:slug}`).
  - Access System Admin panel at `/sysadmin`.
- [ ] **Storage & Media:** Verify user avatars and uploaded documents display without 404s.
- [ ] **Public Enquiries Form:** Submit an enquiry on `/` and verify it appears in the CRM.
- [ ] **Stripe Donations:**
  - Test `/donate` checkout redirect and return to `/donate/thank-you`.
  - Check Stripe webhook delivery status in Stripe dashboard (should return HTTP 200).
  - Verify donation record and Gift Aid declaration recorded in database.
- [ ] **Yeastar PBX:**
  - Test contact lookup endpoint: `https://recoverynetwork.org.uk/api/yeastar/contacts`.
  - Confirm phone calls journal to service users or Unmatched Calls.
- [ ] **Email Delivery:** Test password reset link delivery to ensure Hostinger SMTP/Postmark is sending.

---

## 7. Post-Cutover Actions on Hostinger

- [ ] **Redirect Demo Domain in Hostinger:**
  - On `spinney.j-commerce.co.uk`, add a redirect in hPanel -> **Domains** -> **Redirects** (or in `.htaccess`):
    ```apache
    RewriteEngine On
    RewriteCond %{HTTP_HOST} ^spinney\.j-commerce\.co\.uk$ [NC]
    RewriteRule ^(.*)$ https://recoverynetwork.org.uk/$1 [R=301,L]
    ```
- [ ] **Reset DNS TTL:** Set TTL back to 14400 or 86400 seconds.
- [ ] **Enable Hostinger Automated Backups:** In hPanel -> **Files** -> **Backups**, ensure daily snapshots are enabled.
