# Santa Shuffle

Read README.md and PRODUCT.md before changing scope. Preserve the agreed visual direction in DESIGN.md.

Laravel 13 owns validation, authorization, persistence and drawing. React + Inertia renders explicit allow-listed props. Never serialize complete Participant or Exchange models to the frontend.

Use Laravel database transactions and the same exchange lock for mutations affecting a draw. SQLite uses IMMEDIATE transactions; PostgreSQL uses row locks. A completed draw is immutable. Keep database queue insertion in the transaction so failures cannot partially commit a draw.

Validation: `php artisan test`, `vendor/bin/pint --test`, `npm run typecheck`, `npm run format:check`, `npm run build`. Run database tests against isolated SQLite and PostgreSQL databases. Never run RefreshDatabase against a real instance.

Follow official documentation for installed major versions before changing setup. Keep composer.lock and package-lock.json committed. Do not publish, deploy or send real invitations unless explicitly requested. Keep .env files, database files, private links and logs out of Git.
