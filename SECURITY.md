# Security policy

## Supported versions

Before the first tagged release, security fixes target the default branch. After releases begin, only the latest stable release receives security updates unless maintainers explicitly announce another supported line. There is no guaranteed response-time or security certification.

## Reporting a vulnerability

Use [Security → Report a vulnerability](https://github.com/romainfrezier/santa-shuffle/security/advisories/new) on the canonical GitHub repository when private vulnerability reporting is enabled. Include the affected version, a minimal reproduction using synthetic data, the expected boundary and the observed impact. Do not test against somebody else's instance or include real participant data, login codes, private links or credentials.

If private reporting is unavailable, contact the repository owner through a private contact they publish. Do not open a public exploit report. If no private contact exists, open a minimal issue asking maintainers to enable private reporting, without vulnerability details. Maintainers must enable private reporting before announcing public availability; this repository does not invent an unmonitored security email address.

## Security boundaries

- Organizer access requires verification of the email inbox through a short-lived, single-use code. This is passwordless authentication, not a second factor; protect the mailbox itself.
- Participant access links are bearer secrets. Rotate a compromised link from event management and avoid logging `/access/*` URLs in reverse proxies or analytics.
- The organizer interface does not expose assignments, participant emails or wishes. The server operator can read the database; there is no end-to-end encryption.
- HTTPS, a real SMTP service, a secret `APP_KEY`, private backups and timely updates are required for real use. Keep debug mode off. Do not use the log mailer in production.
- This application is intended for a team-sized instance. An open public service needs additional abuse controls and operational review.

See [hardening and operations](docs/hardening.md) for lifecycle invariants, SMTP failure behavior and restore tests. [Validation records](docs/validation.md) distinguish local evidence from CI and production verification.
