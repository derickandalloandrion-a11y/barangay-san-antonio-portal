# Installing the security-hardened launch candidate

## Safe local upgrade

1. Stop Apache only if you need to replace the running folder.
2. Make a backup copy of your current project folder. Do not delete it.
3. Extract this ZIP to a new folder named `barangay_san_antonio_portal_launch_demo_secured` under `C:\xampp\htdocs\`.
4. Copy your working `config/database.php` into the new folder's `config` directory. Never upload this file or share its password.
5. Copy your configured `config/mail.php` only if you already have a valid local mail configuration. For local demo email, retain the documented mail-preview setup.
6. Do not import `database/schema_launch_demo.sql` again. This code-only update does not require a schema change.
7. Open the new folder's `public/login.php` URL and test the four demo roles and key service workflows.
8. Keep the original working project folder until the new copy has passed your own checks.

## Local URL example

`http://localhost/barangay_san_antonio_portal_launch_demo_secured/public/`

## Important

The local XAMPP/HTTP setup is for development and acceptance testing only. Do not expose XAMPP directly to the public internet. Public deployment requires approved hosting, HTTPS, real SMTP, least-privilege database credentials, replacement of demo credentials, and completion of the production-readiness checklist.
