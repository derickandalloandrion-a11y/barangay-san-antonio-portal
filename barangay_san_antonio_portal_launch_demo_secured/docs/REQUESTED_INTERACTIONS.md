# Requested service-page interactions

## Document request entry points
- The `More Documents` link opens an accessible sign-in/sign-up dialog.
- Each Most Requested Documents card opens the same dialog.
- The dialog routes Sign in to `login.php` and Sign up to `register.php`.
- Document requests remain protected by the existing authenticated, approved-resident authorization checks in `service.php` and `service_submit.php`.

## Crime report entry points
- `Report Anonymously Here` opens the anonymous report form.
- Selecting a card in Report a Crime opens a dialog with `Report Anonymously` and `File a Formal Complaint` choices.
- Anonymous reports use a separate table and are not attached to a user account. The optional contact field is optional and may reduce practical anonymity if completed.
- Formal complaints route to the sign-in page; after login the user can use the formal report workflow.

## Required database migration
Before accepting anonymous reports, import `database/migration_anonymous_reports.sql` into the dedicated `barangay_san_antonio_portal_launch_demo` database. It creates one new table only and does not alter or delete existing tables. Do not import the full schema again.

Anonymous reports currently receive a reference number and are stored separately. A staff review screen and notification workflow for this separate table are not included in this update; these are required before operational deployment.

## Medicine page
The medicine page follows the supplied wireframe sections: hero headline and image, free medicine/contact banner, medicine categories, health advisory, existing slogan and shared footer. The MEDICINE action scrolls to the authenticated request area. The existing approved-resident checks still apply.
