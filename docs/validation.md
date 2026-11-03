# Local validation records — 2026-11-03

These records describe successive implementation revisions. Historical totals and coverage are not claims about later changes. GitHub CI and production deployment require their own evidence.

## Initial hardening results

- **43 PHPUnit tests**, including 41 product-specific tests and 2 skeleton examples, **3,367 assertions**, passed on isolated SQLite and PostgreSQL 18.
- **40 multiprocess races**: 5 repetitions of 4 scenarios on each engine. Double draw, reveal/reopen, registration/draw and deletion/update. Processes synchronize at a barrier; tests do not infer a collision from an arbitrary sleep.
- **6 Chromium/Playwright scenarios**: creation, reopen/invite/confirm/redraw/reveal, XSS payloads, CSRF rejection, session rotation/logout, cancellation/deletion. Management pages visually checked at 1280px and 390px with no horizontal overflow.
- **Real local SMTP fixture and worker**: successful delivery, temporary rejection/retry, permanent failure after three attempts, worker termination after message receipt and reservation recovery. Ambiguous SMTP acceptance demonstrably permits a duplicate with the same valid link. No real invitations sent.
- **SQLite and PostgreSQL Docker stacks**: app, worker and scheduler started in separate synthetic projects; migrations, HTTP, backup and restoration checked, including token decryption with the preserved APP_KEY. Test resources removed afterward.
- TypeScript (UI and browser tests), Prettier, Pint, Vite production build and strict Composer validation passed.
- `composer audit --locked` and `npm audit` reported no known advisories at the time. Gitleaks 8.30.1 found no secrets in tracked and new non-ignored sources. This is not a security certification.

## Historical coverage measurement

Xdebug 3.5.3 with an early `app/` filter and PHPUnit branch collection measured **96.90% of lines** and **98.98% of branches** at the initial hardening revision. All business controllers and the drawing service had their executable lines covered. Remaining lines included worker concurrency guards and production bootstrapping.

These figures cover the PHP suite within its process. Multiprocess, SMTP and Chromium runs are not merged into the measurement. Path coverage is substantially lower, particularly because of matching-algorithm path combinations. Neither full branch coverage nor a high assertion count proves the absence of bugs.

Ignored local HTML report: `artifacts/coverage/index.html`. Screenshots: `artifacts/manage-desktop.png` and `artifacts/manage-mobile.png`. Reproduction commands and scope: [hardening.md](hardening.md).

## Regressions identified and fixed during hardening

- Reveal serialized with reopening and group mutations.
- Link renewal invalidates old sessions; notification versions invalidate stale deliveries.
- With no-referrer responses, mutation redirects explicitly target the right workspace; validation remembers the current Inertia page without a Referer.
- SQLite purge uses a date comparison independent of midnight timestamp storage.
- Repeated field names across forms have unique HTML identifiers.
- SQLite Docker restoration restores file ownership after copying.

## Evidence boundaries

GitHub Actions is configured for both databases, concurrency, SMTP, Chromium, coverage, dependency audits, secrets and Docker restoration. **No GitHub run is claimed by these historical records.** No remote repository, published image or VPS deployment was produced in these runs.

The preview is local with demonstration data. Production SMTP, HTTPS, reverse proxy and backup retention need live configuration and verification. The app targets a team-sized instance: slow SMTP can briefly block SQLite writes, and interrupted SMTP acceptance can cause duplicates. These checks are not exhaustive browser coverage, public-service load testing or an independent penetration test.

## Currency and visual refresh — 2026-11-03

- 45 PHPUnit tests / 3409 assertions passed independently on SQLite and PostgreSQL, including allowed currencies, invalid codes/arrays, EUR default, omitted-field preservation and post-draw edit protection.
- 7 Playwright scenarios passed: CHF creation, existing event lifecycle/security checks, mobile overflow, keyboard currency selection and reduced-motion shuffle. Desktop/mobile screenshots in ignored `artifacts/` were visually inspected.
- TypeScript, Prettier, Pint, production Vite build and Docker build passed. npm install audit reported zero known vulnerabilities in 151 packages.
- Local Docker preview on port 8128 updated after stopping writers and backing up SQLite to `artifacts/local-before-currency.sqlite` (0600). Migration preserved 2 events and 1 participant; existing events received EUR. Home, create and health routes returned HTTP 200.
- Impeccable detector reported five occurrences of its generic Fraunces popularity warning. Font retained deliberately for the warm stationery direction; this is not a functional failure.
- Previous coverage percentages above describe the prior hardening revision, not this refresh. No new coverage percentage, GitHub CI or VPS deployment claimed.
- Currency migration is additive with database default EUR; rollback drops only the currency column (therefore loses selected currency information). No budget conversion or payment handling.

References used: [Radix Select](https://www.radix-ui.com/primitives/docs/components/select), [GSAP React](https://gsap.com/resources/React/), [Sonner](https://sonner.emilkowal.ski/), [canvas-confetti](https://github.com/catdad/canvas-confetti).


## Passwordless organizer login — 2026-11-03

- 54 PHPUnit tests, 3529 assertions passed on isolated SQLite and PostgreSQL: ownership, legacy link/session bypass prevention, forged owner creation, code expiry, five-attempt cap, resend cooldown and invalidation, browser binding, single-use, mail failure rollback, rate limits, logout and migration rollback/reapply preserving events.
- 8 Playwright journeys passed using real HTTP/CSRF and a private synthetic log-mail sink, including email-code login, multiple owned events, foreign-event isolation, legacy link rejection, session rotation/replay and previous event/XSS flows. Login/dashboard desktop and mobile screenshots visually inspected.
- 50 real-process races passed: 5 cases × 5 runs × both databases, including two simultaneous submissions of one code (exactly one succeeds).
- SMTP fixture tests passed: success, temporary/permanent failure, worker kill/recovery, safe duplicates and event deletion. Organizer notifications now link to login, never grant access by bearer URL.
- TypeScript, Prettier, Pint, Vite and Docker builds passed. Local image config: `sha256:9c58277a986f0daca74de64827a7736040e60f323b20cb4f4a6a1c5e9cbd9fac`.
- Preview migrated with writers stopped and a private SQLite backup at `artifacts/local-before-email-login.sqlite`. No real emails, VPS changes or GitHub CI run. Local codes are logged until SMTP is configured.
- This supersedes earlier organizer magic-link behavior and earlier test/coverage totals; no new coverage percentage is claimed.


## Architecture, localization and open-source preparation — 2026-11-03

- **61 PHPUnit tests / 3,675 assertions passed on both SQLite and PostgreSQL.** Refactoring preserves the HTTP contracts, authorization, transaction boundaries and private projections. New regressions cover language negotiation/persistence, translated validation and mail, queued delivery language, migration rollback, and localized exception pages.
- **12 Vitest unit/component tests and 9 Playwright journeys passed.** Browser journeys cover all four languages, server validation, session persistence, access control and localized 403/404 pages. Desktop and 390px mobile views were inspected in English, French, Spanish and German.
- **50 process races passed** across SQLite/PostgreSQL (five scenarios, five runs per engine), including single-use login codes. SMTP success, temporary/permanent failures, worker interruption/recovery, safe duplicate delivery and deletion tests passed with synthetic recipients.
- **Both shipped Docker database variants passed** startup, migrations, authenticated-route redirect checks, public HTTP checks, backup and encrypted-token restoration on isolated volumes. The fixture now tests login directly and checks protected creation redirects without following the canonical APP_URL outside the random-port test stack.
- PHPStan/Larastan level 5 reported zero errors. Strict TypeScript, Pint, Prettier, production builds and Composer validation passed. ESLint was not forced into the toolchain: the current TypeScript ESLint parser excludes the installed TypeScript 7 version. Reassess when that parser officially supports it.
- Fresh Xdebug 3.5.3 measurement for PHP under `app/`: **97.34% lines (439/451), 95.70% branches (178/186), 50.44% paths (115/228)**. This measurement excludes external processes and browser execution. Reports are ignored local files at `artifacts/coverage-architecture/` and `artifacts/coverage-architecture.txt`; it is not a 100% coverage or security claim.
- `npm audit` and `composer audit --locked` found no known advisories at validation time. Gitleaks 8.30.1 found no secrets in the tracked/new non-ignored source snapshot. No independent penetration test or OS-image vulnerability assessment is claimed.
- Actionlint passed both workflows. Three release-validator tests cover 22 tag cases. The license packaging test passed; a production build collects notices for 101 installed npm dependencies, including font licenses and GSAP-specific terms.
- Two integration defects were fixed with regressions: exceptions raised before locale middleware lost the session language; malformed event UUIDs reached PostgreSQL and produced 500 errors. Shared locale resolution and a shared UUID route constraint now cover these paths. Both malformed and valid-but-missing UUIDs return localized 404 responses.
- Local preview updated after stopping writers and copying SQLite to `artifacts/local-before-architecture-i18n.sqlite` with mode 0600. The additive locale migration preserved **3 events and 2 participants**, retaining French for existing records. Login and malformed-event pages were checked over HTTP in all four languages; distributed license notices returned HTTP 200. App, worker and scheduler restarted successfully.
- Local Docker image manifest: `sha256:5c1bbe6f12faa7ef28528f5b95576d511c0f2ece52087bbf60a67fa7e47386f9`. This is a local build, not a published registry digest. Documentation recording these results was completed after that build.
- `origin` points to `https://github.com/romainfrezier/santa-shuffle.git`. At inspection the remote repository was private and empty. **No source push, release tag, GitHub Actions execution, GHCR publication, visibility change or VPS deployment occurred.** The release workflow is ready to publish amd64/arm64 images on valid pushed version tags after the full CI gate. See [releases.md](releases.md) for repository/package setup and publication.
