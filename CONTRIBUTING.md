# Contributing

Santa Shuffle welcomes focused bug fixes, accessibility improvements, translations and self-hosting improvements. Discuss substantial new behavior in an issue before building it. Follow the [Code of Conduct](CODE_OF_CONDUCT.md) and report vulnerabilities through [SECURITY.md](SECURITY.md).

## Development

Follow the [local setup](README.md#local-development), then create a branch. Use synthetic `.test` addresses and disposable events. Never commit environment files, access links, email codes, databases, logs or screenshots containing personal information.

Read the [architecture guide](docs/architecture.md) before moving responsibilities across layers.

Source identifiers, comments, commit descriptions, pull requests and developer documentation are written in English. User-facing text belongs in translation catalogs, with English, French, Spanish and German kept in sync. Do not add `CLAUDE.md`; repository guidance lives in [AGENTS.md](AGENTS.md).

## Working on behavior

1. Reproduce the behavior with a failing test before changing the implementation.
2. Implement the smallest complete change, then refactor while tests pass.
3. Keep authorization and validation on the server. Reuse the event transaction/lock for mutations; do not expose model internals in Inertia props.
4. Run the relevant focused tests and the checks below. Explain anything you could not verify.

```sh
composer validate --strict
php artisan test
vendor/bin/pint --test
composer analyse
npm run typecheck
npm run format:check
npm run test:unit
npm run build
npx playwright install chromium
npm run test:e2e
npm run test:runtime
```

Backend changes must also run against a disposable PostgreSQL database. Never point `RefreshDatabase` at an existing application database. Concurrency, SMTP and Docker backup/restore checks are described in [hardening.md](docs/hardening.md).

Keep pull requests focused and explain the user-visible change, security implications and tests actually executed. Add migration and rollback notes for schema changes. Preserve existing data and both database engines. Include desktop/mobile evidence for visible changes and test keyboard use and reduced motion.

## Dependencies and releases

Use official documentation for the installed major version. Commit both lockfiles after dependency changes, review the dependency license and run `composer audit --locked` and `npm audit`. Do not introduce an abstraction solely to name a design pattern: each boundary should isolate a real responsibility or make behavior easier to test.

Maintainers publish version tags using the [release procedure](docs/releases.md). Contributions are accepted under the repository's MIT license; by submitting code, you confirm that you have the right to contribute it under that license. No contributor license agreement is required.
