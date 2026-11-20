# Dashboard wireframe upgrade

The authenticated dashboard has been restyled to follow the supplied San Antonio dashboard wireframe while retaining the existing Barangay San Antonio seal, portal colors, PHP authentication, CSRF-protected sign-out, role checks, transaction tables, and workflow review forms.

Included interface sections:
- Location masthead, barangay identity area, signed-in account details, welcome strip, and collapsible left navigation.
- Recent Notifications table populated from the signed-in user's service transactions and, for authorized officials/administrators, available review queues.
- Mission and Vision cards linking to the existing information pages. The approved statement copy is intentionally not invented; the cards identify where approved text should be published.
- Do You Know? carousel linking to existing barangay information pages.
- Latest news and articles cards linking to announcements and search.
- Accomplishments & Awards carousel linking to transparency/public records. No unpublished awards or achievements are fabricated.
- Quick service links, existing account transaction history, appointment/FOI/feedback lists, authorized review queues, and administrator resources.
- Footer with the existing barangay seal and location identity.

The hamburger navigation and carousel previous/next controls are implemented in `public/assets/js/dashboard.js`. Links route to existing portal pages or sections. Financial Reports and Inventory currently link to the portal's transparency/public-records page because dedicated financial-report and inventory modules are not present in this package; those labels are navigation shortcuts, not claims that a full inventory system exists.

No database schema changes are required. Dynamic dashboard data continues to use the existing DashboardData service. The visual update does not create fabricated notifications, official mission/vision wording, awards, or news stories.
