# Security hardening release notes

This is a security-hardening update to the local Launch Demo package. It preserves the existing demo schema and application workflows; it does not run SQL or require a schema re-import.

## Changes in this package

- Strengthened common HTTP response headers: CSP, anti-framing, MIME sniffing protection, referrer and permissions policies, cross-origin isolation headers, and no-store caching for PHP responses.
- CSP allows existing same-origin assets and inline styles for backward compatibility, while inline scripts remain disallowed. Any third-party asset or inline-script requirement must be reviewed before production.
- HTTPS redirect uses the trusted configured `base_url` hostname rather than the untrusted incoming `Host` header.
- Retained strict, cookie-only PHP sessions; added session-name validation and explicit cookie flags. Existing 30-minute idle and 8-hour absolute session controls remain.
- Retained authenticated session ID rotation, CSRF token generation/verification/rotation, and POST-only logout.
- Tightened PDO configuration validation, disabled emulated prepares and persistent connections, and kept exception-mode database handling.
- Restricted server-side view resolution to files within the approved views directory.
- Strengthened registration server-side length/control-character validation, date-of-birth validation, and password complexity requirements.
- Removed account-specific failed-attempt counts from login errors to reduce account-enumeration information.
- Added deny rules for private application/configuration/data directories and storage files.
- Added a small PHP security smoke-test script.

## Verification performed in this release build

- PHP syntax lint across all PHP files.
- Security smoke checks for HTML escaping, CSRF rejection, and view-path traversal rejection.
- Static scan of POST-handling PHP endpoints for CSRF verification calls.

These are source-level checks only. They are not a penetration test, external audit, runtime integration test against the user's local MariaDB, or proof that every business workflow is secure.

## Required before public launch

1. Deploy to an approved production host with a valid TLS certificate; configure `config/app.php` from `config/app.production.example.php` and set the real approved hostname. Do not turn on `force_https` until TLS works.
2. Replace demo accounts and passwords; do not expose test accounts publicly.
3. Configure real SMTP delivery and verify SPF, DKIM, and DMARC. The local mail-preview transport is not production email delivery.
4. Use a dedicated least-privilege MariaDB account; do not use `root` in production.
5. Review CSP against actual assets and remove any compatibility exceptions that are no longer needed.
6. Complete an OWASP ASVS/Top 10 review, dependency and server patch review, independent penetration test, accessibility testing, backup/restore test, and load test.
7. Complete Barangay approval, Data Protection Officer review, privacy impact assessment, retention/breach procedures, and applicable legal/government hosting reviews.
8. Add and test MFA for privileged accounts before enabling public administrative access. MFA is not implemented in this demo.
9. Configure production monitoring, alerting, backups, key management, WAF/DDoS protections, and incident response.

## Important limitations

- This package is still a local demonstration / launch-candidate build, not a security-certified or legally approved government production deployment.
- HTTPS, production email, MFA, encryption-at-rest/key management, WAF, DDoS protection, SIEM, and independent penetration testing depend on the production environment and are not provided by this PHP ZIP alone.
- File-upload workflows are not enabled in this demo; any future upload feature requires a dedicated quarantine/storage design and malware/content scanning.
- Security controls must be tested on the exact deployed PHP, Apache, MariaDB, and hosting configuration.
