# Santa Shuffle

A gift. A secret. A good time together.

A self-hosted Secret Santa for teams, built with Laravel 13, React 19, Inertia 3 and TypeScript. SQLite by default, PostgreSQL optional. English, French, Spanish and German interfaces. Project source is MIT licensed; dependencies retain their [own licenses](THIRD_PARTY_NOTICES.md).

## How it works

Sign in with an email code, create an event, share its invitation link, and wait for participants to confirm by email. Set the gift date, budget, currency and message. Participants can add gift wishes. The organizer can exclude specific giver/recipient pairs and draw names once everyone has confirmed. Each person opens a private envelope to discover only their recipient.

Events support 3–100 participants. Budgets use integer amounts in EUR, CHF, USD, CAD, GBP, JPY or AUD; there are no payments or currency conversions. Before any envelope is opened, the organizer can reopen registration, clear all assignments and draw again after adding people. The first reveal makes the assignments final.

## Organizer access

There are no passwords. `/login` sends a six-digit email code, valid for ten minutes and bound to the requesting browser. A code is single-use, has at most five attempts, and is replaced on resend. Sending is limited to one per minute, five per hour/address and ten per hour/IP; verification has a separate IP limit. The database stores an HMAC, not the plaintext code. Login regenerates the session; logout invalidates it.

`/my-events` lists the events owned by the verified address and accessible to that session. Creating an event always uses the verified address, regardless of submitted fields. Knowing `/manage`, a participant link or a legacy organizer link does not grant organizer access. Control of the mailbox grants organizer access: this is not two-factor authentication.

Each event has participant invitations, confirmation status, exclusions, notification status/retries, pre-draw editing, reopening before reveal, participant-link rotation, organizer session revocation, cancellation before drawing and permanent deletion. The organizer interface receives no participant emails, wishes or assignments. SMTP acceptance does not guarantee inbox delivery.

## Local development

Canonical repository: [romainfrezier/santa-shuffle](https://github.com/romainfrezier/santa-shuffle). Its visibility and release state are managed separately from this source tree.

Requirements: PHP 8.4.1+ with PDO SQLite (and PDO PostgreSQL for PostgreSQL), Composer 2, Node.js 26 LTS and npm 12.2.1 (declared in `package.json`). The Composer lockfile was resolved and tested with PHP 8.5.

```sh
cp .env.example .env
composer install
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
composer run dev
```

Open http://localhost:8000. The development command starts the server, Vite and a queue worker. `MAIL_MAILER=log` writes emails, including private links and login codes, to `storage/logs/laravel.log`: use this **only in development**. With Docker's stderr logging, inspect local app logs privately instead. Never share those logs.

For real email delivery, configure `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` and `MAIL_FROM_ADDRESS`. Use empty credentials for a local SMTP service without authentication. Configure real SMTP and HTTPS before inviting real participants.

## Docker with SQLite

```sh
cp .env.docker.example .env.docker
docker compose --env-file .env.docker build
# Copy the generated key into APP_KEY in .env.docker, once only.
docker compose --env-file .env.docker run --rm --no-deps --entrypoint php app artisan key:generate --show
docker compose --env-file .env.docker run --rm --no-deps app php artisan migrate --force
docker compose --env-file .env.docker up -d
```

The app binds to http://localhost:8080 on loopback only. App, worker and scheduler share the persistent `storage` volume. Migrations are explicit; startup does not run them automatically. Never regenerate `APP_KEY` for an existing installation: stored encrypted tokens depend on it.

On a VPS, put an HTTPS reverse proxy in front of port 8080, set `APP_URL=https://your-domain` and `SESSION_SECURE_COOKIE=true`. Set `TRUSTED_PROXIES` to the proxy's exact IP or narrowly scoped CIDR as seen by the app (comma-separated for multiple proxies). Without this setting, visitors share the proxy's IP rate limits. Only `X-Forwarded-For` is trusted; production URLs still use `APP_URL`. Never use a wildcard or a network containing untrusted clients. A containerized reverse proxy can join the application network and use `app:8080`. Keep its address stable and do not expose plain HTTP directly to the internet. See [proxy configuration](docs/hardening.md#reverse-proxy-and-client-ip-addresses).

To use a published image instead of building locally, set `SANTA_IMAGE` and follow [container releases](docs/releases.md). The tag workflow publishes to GHCR after checks pass. Repository creation, the first successful release and public package visibility are separate setup steps; an image is not assumed to exist before those steps succeed.

## Docker with PostgreSQL

Build the image and generate `APP_KEY` as above. Set a strong `POSTGRES_PASSWORD` in `.env.docker` and use **both files for every command**:

```sh
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml up -d postgres
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml up -d
```

The override configures all three services for PostgreSQL 18 and adds a persistent database volume. For an external database, use only the main file and set Laravel's `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`. Changing engines does not migrate existing data.

## Checks

See the [v0.1.0 dependency audit](docs/dependency-audit-0.1.0.md) for the stable versions and upstream-controlled transitive constraints checked before the first release.

```sh
composer validate --strict
php artisan test
vendor/bin/pint --test
composer analyse
npm run typecheck
npm run format:check
npm run test:unit
npm run build
npm run test:runtime
npx playwright install chromium
npm run test:e2e
```

Use disposable databases only. CI runs PHP and concurrency tests on SQLite and PostgreSQL, plus frontend tests, browser journeys, dependency audits, secret scanning and Docker backup/restore tests. [Validation records](docs/validation.md) distinguish checks actually run locally from configured GitHub Actions and production verification.

## Operations

- **Health:** `docker compose --env-file .env.docker ps`; `/up` verifies Laravel startup, not SMTP delivery.
- **Email failures:** inspect `php artisan queue:failed` in the worker, then retry with `queue:retry all` after fixing SMTP. Automatic retries are bounded. An ambiguous SMTP acceptance can produce a duplicate with the same participant link. Organizer codes are sent synchronously and are single-use.
- **Upgrades:** back up first, stop app/worker/scheduler, build or pull the image, migrate explicitly, then restart all three services. Record the deployed image digest. Base images are pinned by version and digest; Dependabot proposes reviewed updates.
- **Backups:** stop all three writers before copying SQLite storage. Keep `APP_KEY` and private configuration alongside a protected backup. For PostgreSQL use `pg_dump`, and test restoration in isolation. Never run `down -v` on an installation whose data you need.
- **Retention:** event access expires 30 days after the gift date. The scheduler removes expired events and related data daily at 03:00 UTC. Pending expired login codes are purged hourly. Logs and backups need their own retention policy.

See [hardening and operations](docs/hardening.md) for failure handling, migration and restore procedures.

## Privacy and limits

Participant links are access secrets: share them only with their recipients. Random tokens are looked up by SHA-256 hash and stored encrypted with `APP_KEY`; queued jobs are encrypted. Explicit POST activation prevents email scanners from confirming registration just by visiting a link. HTTP-only sessions, Laravel CSRF protection, request limits, private no-cache responses and a no-referrer policy protect browser access. Do not log `/access/*` URLs in your reverse proxy.

The server operator can read the database and assignments: this is **not end-to-end encryption**. Anyone with an invitation link can request registration; share it only with the group. A public service at scale needs additional access/abuse controls and operational review.

The draw uses bipartite matching with augmenting paths and cryptographically shuffled traversal order. It finds a valid solution when one exists and gives each participant exactly one recipient. It is **not uniform across all valid matchings**. Exclusions are directional. Reopening before the first reveal clears old assignments and notifies participants; another explicit draw is required.

## Contributing and project policies

- [Contribution guide](CONTRIBUTING.md) and [code of conduct](CODE_OF_CONDUCT.md)
- [Security policy](SECURITY.md)
- [MIT license](LICENSE) and [third-party notices](THIRD_PARTY_NOTICES.md)
- [Container release procedure](docs/releases.md)
- [Architecture and localization](docs/architecture.md)
- [Product scope](PRODUCT.md) and [design direction](DESIGN.md)

Source identifiers, comments and developer documentation are English. User-facing text is translated into English, French, Spanish and German. Translation catalogs must remain aligned. There is no `CLAUDE.md`; repository guidance lives in `AGENTS.md`.

## Official documentation

- Laravel 13: [installation](https://laravel.com/docs/13.x/installation), [databases](https://laravel.com/docs/13.x/database), [deployment](https://laravel.com/docs/13.x/deployment), [queues](https://laravel.com/docs/13.x/queues), [sessions](https://laravel.com/docs/13.x/session), [rate limiting](https://laravel.com/docs/13.x/rate-limiting), [mail](https://laravel.com/docs/13.x/mail).
- Inertia 3: [server setup](https://inertiajs.com/docs/v3/installation/server-side-setup), [client setup](https://inertiajs.com/docs/v3/installation/client-side-setup), [forms](https://inertiajs.com/docs/v3/the-basics/forms).
- [React](https://react.dev/learn/creating-a-react-app), [FrankenPHP Docker](https://frankenphp.dev/docs/docker/), [Compose file merging](https://docs.docker.com/compose/how-tos/multiple-compose-files/merge/), [PostgreSQL image](https://hub.docker.com/_/postgres).

The project began from `composer create-project laravel/laravel`, with Inertia installed manually. Fonts are self-hosted. No Infomaniak assets are reused.
