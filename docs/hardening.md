# Hardening and operations

Organizer authentication now uses a single-use email code. The original organizer magic-link model is obsolete. Participant bearer-link protections still apply. See the [README](../README.md#organizer-access) for current authentication behavior.

## Event lifecycle and privacy

Each event belongs to its verified organizer email. Direct invitations still require the recipient's explicit confirmation. Duplicate email registration never replaces an existing name or wishes. The maximum is 100 participants; a draw requires at least 3 confirmed participants and no unconfirmed participants.

Reopening is available only before any envelope has been revealed. It clears all assignments, increments the draw version and notifies the group. Exclusions and confirmations remain. After adding and confirming participants, the organizer explicitly draws again. Pending old result notifications become obsolete; an already received link opens the current state, never an old assignment.

Reveal, reopening, registration, exclusions, deletion and link renewal use the same event lock. SQLite uses IMMEDIATE transactions; PostgreSQL locks the event row. Delivery records and database queue inserts are part of the draw transaction. An insertion failure rolls back the whole mutation.

Participant sessions store access versions. Renewal invalidates the old link and its sessions. Organizer event-session revocation preserves the requesting authorized session but requires other browsers to sign in again for that event. Legacy boolean organizer sessions are not accepted. Logout invalidates the session and encrypted Inertia history.

Cancellation is allowed before the draw; it closes access links and notifies the group. An authorized organizer can delete a cancelled event. Permanent deletion is available even after a reveal. Access expires at midnight UTC 30 days after the gift date, and the scheduler deletes expired events at 03:00 UTC. A stopped scheduler delays physical deletion, not access expiry.

## Notifications and failures

Encrypted jobs contain a delivery identifier, not a copied recipient address or access link. The worker reloads the records and checks their versions. Deleted, expired and obsolete notifications are not sent. Stored SMTP errors are generic and do not retain a server response that could contain an address or secret.

There are three attempts, with configured backoffs of 30, 120 and 300 seconds. Worker timeout is 30 seconds, SMTP timeout is 10 seconds, and reservation timeout is 90 seconds. Status distinguishes pending, SMTP-accepted, failed and obsolete. Manual resend and link renewal share a limit of three requests per hour and recipient, including registration requests. Automatic lifecycle notifications are not blocked by this quota but count toward recent history. Management actions have an additional IP/event-session limit.

The event lock stays held during SMTP delivery so that revocation or deletion cannot overtake an authorized send. This is suitable for a team-sized instance; slow SMTP can delay other SQLite writes. There is no exactly-once guarantee: an SMTP acceptance followed by a disconnect or killed worker can cause a retry to deliver a duplicate with the same link. Runtime tests demonstrate this behavior.

After fixing SMTP, use the management resend action or `php artisan queue:retry all`. An accepted delivery does not send again through the same job. Deleting an event makes its jobs harmless; failed jobs are pruned after seven days. Already delivered email cannot be recalled.

Organizer login codes use synchronous mail delivery, with database guards for single use, expiry and the attempt limit. A resend invalidates the previous code. Log-mail delivery is only for local development.

## Reverse proxy and client IP addresses

Set `TRUSTED_PROXIES` to a comma-separated list of the reverse proxy's exact IPv4/IPv6 addresses or narrowly scoped CIDRs, as seen by the application. The default is empty: forwarded headers are ignored. Behind a proxy, leaving it empty makes all visitors share the proxy's login, creation and other IP quotas. For example, `TRUSTED_PROXIES=172.30.0.2` is appropriate only if that is the verified, stable address of your proxy. Do not copy an example subnet without checking your network, use wildcard trust, or trust a shared network containing untrusted clients.

Laravel's built-in middleware accepts only `X-Forwarded-For` from configured proxies. The proxy must append the actual client address or replace untrusted inbound forwarding headers; the rightmost address outside the trusted proxy chain identifies the client. Direct callers outside the allow-list cannot choose their quota by supplying this header. Forwarded host, scheme, port and prefix headers do not change the canonical production origin configured through `APP_URL`. Keep `SESSION_SECURE_COOKIE=true` behind public HTTPS and restrict direct access to the app.

The shipped Compose file loads `TRUSTED_PROXIES` from `.env.docker` through `env_file`. Custom deployments must pass the variable to the application. Recreate containers after changing their environment, and regenerate Laravel's configuration cache if your deployment uses it. Proxy configuration is read from `config/app.php` during service-provider boot, so cached configurations retain the same trust boundary. Confirm distinct client IPs receive independent quotas after deployment without logging private access URLs.

References: [Laravel trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies), [configuration caching](https://laravel.com/docs/13.x/configuration#configuration-caching).

## Migration and rollback

1. Back up the database, storage and APP_KEY. Never regenerate APP_KEY: existing encrypted tokens depend on it.
2. Temporarily close application access. When upgrading an early installation, drain old jobs with the old worker where possible. Stop app, worker and scheduler; do not run an old worker against a new schema.
3. Build or pull the new image, then run `docker compose --env-file .env.docker run --rm --no-deps app php artisan migrate --force`. Include the PostgreSQL override in every command when applicable.
4. Restart all three services. Existing participants and assignments survive the additive migrations. Legacy organizer sessions must sign in with the email address used to create the event.
5. Jobs from the initial implementation without a delivery identifier are ignored by the new worker. Resend invitations or results if they were not sent before the upgrade.

Migration `000002` introduced versioned access and deliveries. Migration `000003` adds currency with EUR as the database default; reversing it discards selected currency information but does not convert budgets. Migration `000004` adds login codes without altering events; reversing it deletes pending codes. Returning to code that accepts legacy organizer links would undo the authentication boundary and is not a safe security rollback.

Migration `000005` adds locale fields to events, participants and deliveries. Existing records default to French to preserve their original language; new records capture the selected request or recipient language. The worker uses the delivery's recorded locale and restores its previous process locale afterward. Reversing the migration drops only these language preferences. Deploy code and schema together, restarting long-running workers after migration.

Restore a matching old image and backup together when needed; do not downgrade the schema after new draws without assessing data loss. Migration rollback is tested only in disposable environments with no new data to preserve. Read release-specific migration notes before each upgrade.

## Backup and purge

The scheduler service runs `schedule:work`. Without Docker, configure Laravel's scheduler with `php artisan schedule:run` every minute. `php artisan events:purge` immediately removes expired events.

For SQLite, stop app, worker and scheduler before copying storage. Keep APP_KEY separately and privately. Restored files and their parent directory must be writable by www-data (UID 33 in the image). `docker cp` can change ownership; restore ownership before starting services.

For PostgreSQL, use `pg_dump -Fc` and restore into an isolated database with `pg_restore --exit-on-error`. Check both data and token decryption using the original APP_KEY. Back up private configuration as well. App-level purge does not alter backups or delivered email; apply separate retention to backups and logs. Do not use `MAIL_MAILER=log` or log `/access/*` reverse-proxy URLs in production.

## Reproducible checks

```sh
php artisan test
php tests/Runtime/concurrency.php
php tests/Runtime/mail.php
npm run test:unit
npm run build
npx playwright install chromium
npm run test:e2e
# Xdebug installed, with coverage enabled only for this command:
XDEBUG_MODE=coverage php -d auto_prepend_file=tests/coverage-filter.php vendor/bin/phpunit --path-coverage --coverage-html=artifacts/coverage --coverage-text=artifacts/coverage.txt
# Docker available, using a freshly built local image:
docker build -t santa-shuffle:local .
python3 tests/Runtime/compose.py sqlite
python3 tests/Runtime/compose.py pgsql
```

For PostgreSQL PHPUnit runs, set DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD and **a dedicated disposable** DB_DATABASE=santa_shuffle_test. RefreshDatabase erases its contents. PostgreSQL concurrency tests require a database name containing `test`, create a separate schema and remove it afterward. SQLite race tests use a dedicated temporary file. SMTP and browser tests use `.test` addresses and local fixtures only, with no real outbound email.

Compose tests create randomly named projects and remove only their synthetic containers and volumes. `SANTA_TEST_SUBNET` selects a free subnet when Docker's automatic pools are exhausted. Restore tests check data and token decryption after a file copy or pg_restore.

| Area | Automated evidence |
| --- | --- |
| Authorization | Anonymous, participant, foreign organizer/event, protected-field injection |
| Sessions | Explicit activation, expiry, renewal, legacy sessions, logout and fixation |
| Input | SQL treated as data, invalid lengths/types, XSS rendered as text in browser |
| HTTP | CSRF enabled outside testing, rate limits, responses without secrets |
| Draw | Exclusions, impossible graphs, uniqueness, 3–100 participants, real races |
| Email | Local SMTP, temporary/permanent rejection, killed-worker recovery, safe duplicates, obsolete jobs |
| Retention | Cascading deletion, purge boundary, SQLite/PostgreSQL restoration |

PHP coverage does not include Chromium journeys or external processes. Branch percentages do not prove all path combinations. See [validation.md](validation.md) for observed results, separately from CI and production claims.

Sources: [Laravel transactions](https://laravel.com/docs/13.x/database), [queues](https://laravel.com/docs/13.x/queues), [scheduler](https://laravel.com/docs/13.x/scheduling), [Playwright webServer](https://playwright.dev/docs/test-webserver), [Xdebug coverage](https://xdebug.org/docs/code_coverage), [OWASP WSTG](https://owasp.org/projects/web-security-testing-guide).
