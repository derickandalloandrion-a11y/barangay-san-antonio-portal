# Production Readiness Checklist

This document separates features present in the local demo from requirements that still need real-world configuration, approval, or independent testing.

## Trust and identity

- [ ] Obtain and configure an authorized `.gov.ph` domain.
- [ ] Install and renew a valid TLS certificate; set `force_https` to `true` only after HTTPS works.
- [ ] Verify the Barangay seal, official name, mandate, leadership, office address, hotline, official email, and office hours.
- [ ] Review all placeholder content and remove the local-demo ribbon only after authorization and launch approval.

## Public information

- [ ] Publish the official Transparency Seal.
- [ ] Publish approved budgets, procurement / bidding notices, annual reports, and datasets.
- [ ] Confirm Citizen’s Charter requirements, fees, processing times, channels, and service commitments.
- [ ] Publish verified laws, ordinances, resolutions, notices, and dated announcements.
- [ ] Approve an FOI procedure, receiving officer, response timelines, and exemptions / redaction workflow.

## Accessibility and inclusion

- [ ] Complete a WCAG 2.1 AA audit with keyboard and screen-reader testing.
- [ ] Test color contrast, form errors, focus order, captions, and accessible document downloads.
- [ ] Have all public content translated and reviewed in Filipino and any local language selected by the Barangay.
- [ ] Conduct user testing with residents, including people with disabilities and low-bandwidth devices.
- [ ] The current language selector only translates a limited set of UI labels; it is not a complete translation.

## Online services and integrations

- [ ] Approve each service workflow, eligibility rule, document requirement, fee, and staff responsibility.
- [ ] Configure a formally approved payment gateway before accepting payments. No payment collection is implemented here.
- [ ] Obtain legal / technical authorization before PhilSys or eGovPH integration. No national ID or SSO integration is implemented here.
- [ ] Approve MFA for staff and privileged accounts. MFA is not implemented in this local demo.
- [ ] Configure real SMTP delivery and domain SPF, DKIM, and DMARC. The included local mail-preview transport is not real email.
- [ ] Add SMS / push notifications, digital signatures, QR verification, API interoperability, GIS, or chatbot only with approved providers and documented security controls.

## Privacy and security

- [ ] Designate the Data Protection Officer and publish approved DPO contact information.
- [ ] Complete a privacy impact assessment and legal review under the Data Privacy Act of 2012.
- [ ] Approve data minimization, retention, archiving, disposal, data-subject request, and breach-notification procedures.
- [ ] Implement encryption at rest and managed key handling on production infrastructure.
- [ ] Conduct OWASP ASVS / Top 10 review, dependency scanning, secure code review, and penetration testing.
- [ ] Configure production CSP, HSTS, TLS 1.2+, WAF, DDoS mitigation, bot protection, SIEM / alerting, and integrity monitoring.
- [ ] Review staff privileges and enforce separation of duties. The demo RBAC model is a starting point, not a completed governance policy.

## Operations and resilience

- [ ] Select approved government / accredited hosting and document hosting-provider security controls.
- [ ] Configure encrypted off-site backups, retention, and tested restoration procedures.
- [ ] Define RTO / RPO and disaster recovery responsibilities.
- [ ] Configure uptime monitoring, maintenance windows, patching, incident response, and escalation.
- [ ] Assign content owners and approvers; review public content before publishing.
- [ ] Establish records retention and archive rules.
- [ ] Add privacy-safe analytics only after approval and notice.

## Final verification

- [ ] Run functional tests on the exact production PHP / MariaDB versions.
- [ ] Run load, security, accessibility, backup-restore, and cross-browser / mobile tests.
- [ ] Test with real citizens and barangay staff.
- [ ] Obtain legal, DPO, agency, and applicable DICT / NPC review and sign-off.
- [ ] Replace all demo credentials and remove test accounts before public deployment.

**Do not describe this package as legally compliant, security-audited, or production-certified until the relevant reviews and tests have actually been completed.**
