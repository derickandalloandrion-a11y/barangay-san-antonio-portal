# Service page wireframe update

The public service pages now follow the supplied reference layouts: document list and most-requested cards, report category grid and hotline band, medicine hero, ad/search/slogan areas, and FAQ accordions. Submission controls are gated behind authentication. Document and medicine requests additionally require an approved Resident verification. Existing service_submit.php transaction, status-history, audit logging, and CSRF protections are retained.

Routes: `service.php?type=barangay-clearance`, `service.php?type=incident-report`, `service.php?type=medicine-assistance`.

The medicine hero uses CSS illustration because the supplied screenshots do not include a separate reusable photograph asset.
