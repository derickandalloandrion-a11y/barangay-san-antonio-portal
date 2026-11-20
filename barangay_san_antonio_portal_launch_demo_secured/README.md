# Barangay San Antonio Portal — Consolidated Local Demo

This release merges the strongest reusable parts of the three supplied ZIP packages:

- **Phase 4 dashboards and authorization:** dashboard shells, session protection, CSRF, RBAC foundation, and official-position authorization.
- **Navigation and registration upgrades:** the existing branded public layout, responsive navigation, Resident/Visitor registration, and email-verification flow.
- **Presentation demo upgrades:** article detail pages, the three-card “View More Articles” expansion, 16:9 media area, and document / incident / medicine workflows.

The original CSS theme and supplied Barangay San Antonio seal are retained. The seal is reused as a temporary visual asset where no approved photograph or media file was supplied.

## Important deployment status

**This is a functional local demonstration / launch-candidate package, not a certified production government website.** It is not yet authorized to represent an official `.gov.ph` service. The production-readiness checklist in `docs/PRODUCTION_READINESS.md` identifies items that require real agency information, legal approval, hosting, certificates, and external provider integrations.

Do not publish sample accounts, placeholder contact information, draft Citizen’s Charter entries, or demonstration content as official public information.

## 1. Requirements

- XAMPP with Apache and MariaDB 10.4+ (the project targets PHP 8.0+).
- PHP extensions: PDO, PDO MySQL, OpenSSL; `mbstring` is recommended.
- VS Code or another PHP editor.
- Browser access to phpMyAdmin.

## 2. Install in XAMPP

1. Extract this ZIP into:

   `C:\xampp\htdocs\barangay_san_antonio_portal_launch_demo\`

2. Copy:

   `config\database.example.php`

   to:

   `config\database.php`

3. Open `config/database.php`. Set your MariaDB host, port, database, username, and password. The default database name is:

   `barangay_san_antonio_portal_launch_demo`

4. Open phpMyAdmin:

   `http://localhost/phpmyadmin/`

5. Import:

   `database/schema_launch_demo.sql`

   The SQL creates a **new separate database** and seeds demo roles, permissions, and four testing accounts. It contains no `DROP TABLE`, `TRUNCATE`, or `ALTER` statements. Do not import it into any protected or existing Barangay database.

6. Start Apache and MySQL in XAMPP.

7. Open:

   `http://localhost/barangay_san_antonio_portal_launch_demo/public/`

If you configure an Apache virtual host, set its `DocumentRoot` to this project's `public` folder. Do not change or overwrite your existing portal virtual host unless you intend to switch projects.

## 3. Temporary demonstration accounts

All four accounts are pre-verified in the demo database. Change these passwords before any shared or public demonstration.

| Role | Username | Temporary password |
|---|---|---|
| Barangay Administrator | `portal_admin_test` | `PortalAdmin2026!ChangeMe` |
| Resident | `portal_resident_test` | `PortalResident2026!ChangeMe` |
| Barangay Official | `portal_official_test` | `PortalOfficial2026!ChangeMe` |
| Visitor | `portal_visitor_test` | `PortalVisitor2026!ChangeMe` |

The Resident demo account has an **APPROVED** resident-verification record so document and medicine workflows can be tested. The Official demo account has a demonstration Barangay Captain assignment that is marked VERIFIED and ACTIVE. These are test fixtures, not real official appointments.

## 4. Test email verification without Gmail

The included `config/mail.php` uses a **local file-preview transport**. It does not send email over the internet.

1. Register a new Resident or Visitor using an email address you control.
2. Open Command Prompt.
3. Run:

   ```cmd
   cd /d C:\xampp\htdocs\barangay_san_antonio_portal_launch_demo
   C:\xampp\php\php.exe scripts\process_email_outbox.php
   ```

4. Open the generated file in:

   `storage\mail-preview\`

5. Copy the verification URL from the preview file and open it in your browser.

6. The account should then become email-verified and eligible to sign in.

The token is single-use and expires 15 minutes after the local preview transport accepts it. For real delivery, change `config/mail.php` to `driver => smtp` and configure an approved SMTP account. For Gmail, use a Google App Password, not your regular Gmail password. Gmail SMTP has not been configured or tested in this release.

If you use a different local URL or virtual host, update `base_url` in `config/mail.php` before running the worker.

## 5. What is functional in this local demo

### Public-facing pages

- Existing branded homepage, navigation dropdowns, responsive layout, seal asset, and article cards.
- Individual article detail pages.
- “View More Articles” expands exactly three additional cards in place.
- Responsive 16:9 hymn/media placeholder. No real video was supplied.
- Public officials directory reads only verified, active, in-term assignments.
- Public search across portal pages and published content.
- Transparency & Open Data listing with category and text filters.
- Citizen’s Charter listing, showing only published entries.
- Laws & Issuances listing with search.
- FOI request form for signed-in users.
- Appointment request form and request history.
- Feedback / complaints form with a honeypot and basic rate limiting.
- Draft privacy notice and data-subject request submission.
- Contact and agency identity placeholders can be updated by an authorized Administrator.

### Accounts and authorization

- Public registration is limited to Resident and Visitor.
- Email verification required before login.
- Password hashing, login lockout after five failed account attempts, and an additional IP-based login rate limit.
- Session idle timeout (30 minutes), absolute lifetime (8 hours), session ID regeneration after login, cookie-only sessions, HttpOnly, SameSite=Lax, and CSRF protection.
- POST-only CSRF-protected logout.
- Resident, Visitor, Official, and Administrator account types.
- Officials receive permissions only through exactly one verified, active, in-term assignment and the assigned position's `position_permissions`. They do **not** inherit generic `role_permissions`.
- Administrator can review service queues, manage position permissions, verify / activate official assignments, manage account status, publish content, and update public portal settings.

### Online workflows

- Document requests.
- Incident reports.
- Medicine-assistance requests.
- Appointment requests.
- FOI requests.
- Feedback and complaints.
- Data-subject requests.
- Review queues, allowed status transitions, status history for service / appointment / FOI requests, and audit-log records.

Document and medicine requests require an approved Resident verification record. Incident reporting is not an emergency dispatch service. Appointment requests are not confirmed until staff approve them. Medicine availability and document issuance remain subject to official review.

### Accessibility and performance foundations

- Skip-to-content link, keyboard-operable dropdowns, visible focus indicators, alt text for the supplied seal, text scaling, high-contrast toggle, and reduced-motion CSS.
- English / Filipino selector translates a limited set of common interface labels. It is **not** a complete translation of all content.
- Mobile-first responsive layout and no third-party font or image dependencies.
- PWA manifest and a service worker that caches static assets only. Authenticated pages, PHP pages, forms, and storage files are never cached.

## 6. Database schema

`database/schema_launch_demo.sql` creates **27 tables and 40 foreign-key constraints** for this separate local demo. It includes:

- Users, profiles, roles, permissions, role permissions, official positions, official assignments, position permissions.
- Resident verification.
- Service transactions and status history.
- Email verification tokens and outbox.
- Appointment requests and status history.
- FOI requests and status history.
- Feedback submissions.
- Public content and content revisions.
- Public transparency documents.
- Citizen’s Charter entries.
- Audit logs, consent records, data-subject requests, and system settings.

This is not the original protected 51-table database and does not reconstruct the separate 64 CHECK constraints. It is a new schema designed for this consolidated demo application.

## 7. Backup

Run `scripts\backup_database.bat` from Windows. It invokes XAMPP's `mysqldump`, prompts for the MariaDB password, and writes a SQL backup under `backups\`. This is a local backup helper only; configure encrypted off-site backups and test restores before production.

## 8. Before public launch

Review `docs/PRODUCTION_READINESS.md`. At minimum, public launch requires:

- An authorized `.gov.ph` domain and valid HTTPS certificate.
- Verified Barangay identity, leadership, mandate, address, hotline, email, and office hours.
- Approved Transparency Seal / budget / procurement / annual report / open-data publications.
- An approved Citizen’s Charter with real requirements, fees, and processing times.
- A formally approved FOI process and designated FOI receiving officer.
- A DPO-approved privacy notice, retention schedule, privacy impact assessment, and breach-response procedure.
- Production mail delivery and domain SPF / DKIM / DMARC.
- Approved hosting, backups, monitoring, WAF / DDoS controls, vulnerability assessment, accessibility audit, load testing, and legal / DICT / NPC review.
- Approved payment, PhilSys / eGovPH, MFA, SMS, digital signature, QR, API, GIS, and other integrations if they are required.

The portal does not currently integrate online payments, PhilSys / eGovPH SSO, MFA, SMS / push notifications, QR-signed certificates, a public API, GIS, chatbot, SIEM, WAF, DDoS protection, or government cloud hosting. No payment or identity provider credentials were supplied. These are not simulated as if they were live.

## Role dashboard upgrade

See `docs/ROLE_DASHBOARDS_UPGRADE.md` for the four role-specific dashboard templates, navigation differences, cancellation security rules, temporary sample-data notes, and installation instructions. No SQL migration is required for this update.


## Security-hardened launch candidate

This package includes the security hardening notes and safe local installation steps in `docs/SECURITY_HARDENING_RELEASE.md` and `docs/SECURITY_INSTALLATION.md`. It remains a local demo/launch candidate and is not security-certified or approved for public government production use. Complete `docs/PRODUCTION_READINESS.md` before any public deployment.
