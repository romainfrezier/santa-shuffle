# Santa Shuffle

Read README.md and PRODUCT.md before changing scope. Preserve the agreed visual direction in DESIGN.md.

Laravel 13 owns validation, authorization, persistence and drawing. React + Inertia renders explicit allow-listed props. Never serialize complete Participant or Exchange models to the frontend.

Use Laravel database transactions and the same exchange lock for mutations affecting a draw. SQLite uses IMMEDIATE transactions; PostgreSQL uses row locks. A draw becomes immutable after its first reveal. Reopening before any reveal clears all assignments, increments the draw version and uses the same event lock as reveal. Keep database queue insertion in the transaction so failures cannot partially commit a draw.

Validation: `php artisan test`, `vendor/bin/pint --test`, `composer analyse`, `npm run typecheck`, `npm run format:check`, `npm run test:unit`, `npm run build`. Run database tests against isolated SQLite and PostgreSQL databases. Never run RefreshDatabase against a real instance.

Follow official documentation for installed major versions before changing setup. Keep composer.lock and package-lock.json committed. Do not publish, deploy or send real invitations unless explicitly requested. Keep .env files, database files, private links and logs out of Git.

Keep source identifiers, comments and developer documentation in English. Translate user-facing text into English, French, Spanish and German; preserve catalog parity. Keep guidance in AGENTS.md, never CLAUDE.md. Start behavior changes with a failing regression test and keep refactoring behavior-preserving.
