# AMC Monitoring MVP

## Vision
Deliver a lightweight web tool that gives apron control teams real-time visibility into aircraft movements so they can keep the stand plan accurate without relying on spreadsheets or radio calls.

## Primary Users
- Apron controllers who update live stand assignments and flight timings.
- Duty managers who audit daily performance and resolve stand conflicts.
- Administrators who maintain user access.

## Core Jobs To Be Done (Launch Scope)
1. Record arrivals, departures, and RON (remain overnight) statuses for each stand.
2. Review the apron at a glance through an interactive stand map.
3. View and edit the movement log in a tabular format with inline updates.
4. Generate a daily immutable snapshot for operational audits and shift handovers.
5. Authenticate users and enforce role-based permissions for critical actions.

## Must-Have Features
- Secure login and session handling (operator/admin roles at minimum).
- Apron map (`index.php`) with modal-based entry of aircraft data.
- Master table (`master-table.php`) with filtering, sorting, and inline create/update.
- Snapshot manager (`snapshot-manager.php`) to create, list, view, and delete daily archives.
- MySQL persistence via `dbconnection.php` and `config.php` for movement, flight reference, and user tables.
- Basic logging to support troubleshooting and auditability.

## Success Metrics (First 60 Days)
- >=90% of aircraft movements recorded in the system within 5 minutes of the event.
- Every scheduled shift produces a daily snapshot signed off by a supervisor.
- Zero unresolved P0/P1 incidents triggered by authentication or authorization failures.

## Out of Scope for MVP
- Automated flight schedule imports or integrations.
- Mobile-optimized interface beyond the current responsive tweaks.
- Advanced analytics, predictive stand allocation, or machine-learning features.
- External API endpoints for third-party consumers.

## Key Assumptions
- Operators have browser access on the apron network with reliable connectivity.
- Stand layout and identifiers remain stable during the pilot period.
- Existing PHP + MySQL stack is acceptable for the hosting environment.

## Risks & Mitigations
- **Data accuracy risk:** Mitigate with inline validations and post-shift snapshot review.
- **User adoption risk:** Provide quick-reference training and capture feedback in weekly check-ins.
- **Single point of failure:** Plan for periodic database backups and snapshot exports.

## Next Validation Steps
1. Run a live day-in-the-life exercise with apron staff using the apron map and master table.
2. Review snapshot outputs with duty managers to confirm audit needs are met.
3. Collect login/use metrics to refine role permissions and identify friction points.
