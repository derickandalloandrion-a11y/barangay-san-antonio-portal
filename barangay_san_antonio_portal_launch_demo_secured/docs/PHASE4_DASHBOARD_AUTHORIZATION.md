# Role-Based Authorization

The consolidated demo uses four account types:

- RESIDENT — permissions granted through `role_permissions`.
- VISITOR — restricted permissions granted through `role_permissions`.
- OFFICIAL — no generic role permissions are used. Authorization requires exactly one verified, active official assignment within its term, an active official position, and the requested permission in `position_permissions`.
- ADMINISTRATOR — administrative permissions granted through `role_permissions`.

The authorization service fails closed when required schema elements are missing, when an account has zero or multiple active roles, or when an Official has zero or multiple valid assignments.

The official assignment date columns in this demo are `term_start` and `term_end`. `user_roles` does not require an `assignment_status` column. This intentionally follows the approved project data model rather than the mismatched expectations in earlier ZIP versions.

See `public/rbac_manage.php` for the Administrator's position-permission and assignment management screen.
