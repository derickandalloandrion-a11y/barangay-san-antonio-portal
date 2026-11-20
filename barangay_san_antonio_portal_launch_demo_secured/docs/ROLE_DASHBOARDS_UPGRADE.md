# Role-specific dashboard upgrade

This update introduces four role-specific dashboard view templates selected by the authenticated role in `public/dashboard.php`:

- `app/Views/dashboards/administrator.php`
- `app/Views/dashboards/resident.php`
- `app/Views/dashboards/official.php`
- `app/Views/dashboards/visitor.php`

They share `app/Views/dashboard-shared.php` for consistent branding, navigation, account menu, responsive layout, and accessible interactions. This keeps authentication and authorization centralized while allowing each role's navigation and content to differ.

## Data and access rules

- Residents see their own service transactions and appointments. Their document/medicine eligibility continues to depend on approved resident verification and existing permissions.
- Visitors receive the requested limited navigation. Existing Visitor permissions remain unchanged; being signed in does not grant Resident-only services.
- Barangay Officials are treated as Barangay Functionaries and see service queues only when their currently verified active position assignment grants the relevant review permission. No generic role-permission fallback is introduced.
- Administrators see cross-account service queues and system-wide counts, subject to the Administrator role and its existing permission grants.
- Account profile information is read from `users` and `user_profiles`. The current schema does not provide a profile-photo blob/storage table, so the menu uses an initials avatar rather than inventing a photo.
- User cancellation is allowed only for the user's own `SUBMITTED` transaction or appointment.
- Administrator cancellation is separately validated server-side and can cancel any non-cancelled service transaction or appointment across accounts. All cancellation actions use POST, CSRF validation, row locking, transaction history, and audit logging.

## Temporary demonstration content

Mission, Vision, Objectives, news cards, community facts, accomplishments, sample financial comparisons, and inventory examples are clearly presented as temporary sample content. Replace them with approved official information. The existing schema has no dedicated financial-ledger or inventory/loan tables; these two panels are therefore illustrative only and do not claim to be live accounting or inventory records.

## Existing schema

No schema changes or SQL imports are required for this dashboard update. It uses the existing `users`, `user_profiles`, `user_roles`, `roles`, `permissions`, `role_permissions`, `official_assignments`, `official_positions`, `position_permissions`, `service_transactions`, `service_status_history`, `appointment_requests`, `appointment_status_history`, `content_items`, and `audit_logs` tables.

## Installation

1. Back up the current project folder.
2. Extract the upgrade ZIP.
3. Copy the extracted project contents into the existing project folder.
4. Preserve the local `config/database.php` file.
5. Do not import or replace the database schema.
6. Restart Apache and sign in with each demo role to inspect its dashboard.

## Validation and limits

PHP syntax and JavaScript syntax checks were run when this package was prepared. A live MariaDB/XAMPP integration test was not possible in this build environment. Verify database-backed dashboards and cancellation workflows locally before relying on them. Financial reports and inventory are sample-only until dedicated data structures and approved source data are provided.
