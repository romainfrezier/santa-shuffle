# v0.1.0 dependency audit

Checked on 2026-11-03 against the official npm and Composer registries, upstream releases and Docker Hub manifests. This is a dated release checkpoint, not a promise that dependencies remain current forever.

## Stable versions

| Component | Release target |
| --- | --- |
| PHP / FrankenPHP | 8.5.12 / 1.13.4 |
| Composer | 2.11.1 |
| Node.js | 26.13.0 LTS |
| Debian application and build images | 13 (Trixie) |
| PostgreSQL (optional) | 18.6 |
| SQLite | 3.46.1 (Debian-maintained runtime library) |
| Laravel / Inertia Laravel | 13.40.0 / 3.7.0 |
| React / Inertia React | 19.3.1 / 3.11.0 |
| TypeScript / Vite | 7.0.2 / 8.3.5 |
| PHPUnit / Vitest / Playwright | 13.4.6 / 5.1.0 / 1.64.1 |
| Gitleaks | 8.30.1 |

All 25 direct npm dependencies match their registry `latest` stable release. After the PHPUnit 13.4.6 patch, `composer outdated --locked --format=json` reports no outdated packages, including transitive dependencies. Node 27 prereleases and PostgreSQL 19 release candidates are excluded.

Docker bases use explicit versions and multi-platform manifest digests. CI uses Node 26 and PHP 8.5. The PHP development dependency tree requires at least PHP 8.4.1; the release image uses PHP 8.5.12. SQLite is supplied by the PHP/Debian image rather than a separately versioned application dependency. Distribution libraries follow Debian's maintained packages and backports; they are not claimed to match every upstream project's newest version.

## GitHub Actions

All actions are pinned to the commit corresponding to their latest stable release at this checkpoint: checkout 7.0.1, setup-node 7.1.0, setup-php 2.40.0, upload-artifact 7.0.2, setup-qemu 4.4.0, setup-buildx 4.4.1, login 4.7.0, metadata 6.2.0 and build-push 7.4.0. The setup-php pin uses the commit referenced by its annotated tag, rather than the tag-object SHA.

## Upstream-controlled npm versions

`npm outdated --all` also reports dependencies that the latest parent libraries deliberately constrain below another stable release. These 24 installed package names are current within the upstream constraints. There are no blanket overrides: changing these major versions or exact pins would substitute an untested dependency tree for the one supported upstream.

| Package | Installed → newest stable | Constraint owner |
| --- | --- | --- |
| `@oxc-project/types` | 0.156.0 → 0.157.0 | Rolldown exact pin |
| `es-module-lexer` | 2.3.2 → 3.0.4 | Vitest 2.x range |
| `magic-string` | 0.30.21 → 1.4.5 | Inertia Vite 0.30.x range |
| `nanoid` | 3.3.20 → 6.0.2 | PostCSS 3.x range |
| `picomatch` | 2.3.2 → 4.0.7 | vite-plugin-full-reload 2.x range |
| `undici-types` | 8.9.0 → 8.12.3 | Node types ~8.9.0 |
| `why-is-node-running` | 3.2.1 → 3.2.3 | Vitest exact pin |
| `yargs` | 18.0.0 → 18.2.0 | concurrently exact pin |
| `chalk` | 5.6.2 → 6.0.1 | concurrently exact pin and Ink 5.x range |
| `supports-color` | 10.2.2 → 11.0.0 | concurrently exact pin |
| `ink` | 7.1.1 → 8.0.0 | Laravel Multiplex 7.x range |
| `react-reconciler` | 0.33.0 → 0.34.0 | Ink 0.33.x range |
| `scheduler` | 0.27.0 → 0.28.0 | Ink and reconciler 0.27.x range |
| `ansi-styles` | 6.2.3 → 7.0.0 | Terminal rendering dependencies on 6.x |
| `cli-cursor` | 4.0.0 → 5.0.0 | Ink 4.x range |
| `restore-cursor` | 4.0.0 → 5.1.0 | cli-cursor 4.x range |
| `onetime` | 5.1.2 → 8.0.0 | restore-cursor 5.x range |
| `mimic-fn` | 2.1.0 → 5.0.0 | onetime 2.x range |
| `signal-exit` | 3.0.7 → 4.1.0 | Ink and restore-cursor 3.x ranges |
| `slice-ansi` | 9.0.1 → 10.0.0 | Ink 9.x range |
| `string-width` | 7.2.0 → 8.3.0 | cliui, yargs and wrap-ansi 7.x ranges |
| `emoji-regex` | 10.6.0 → 11.0.0 | string-width 10.x range |
| `wrap-ansi` | 9.0.2 → 10.0.2 | cliui 9.x range |
| `escape-string-regexp` | 2.0.0 → 5.0.0 | stack-utils 2.x range |

Absent optional peers and binaries for other operating systems are not installed outdated packages. Registry metadata was refreshed directly to avoid stale npm cache results.

## Recheck and validation

```sh
composer outdated --locked --format=json
npm outdated --prefer-online --json
npm outdated --all --prefer-online --json
composer audit --locked
npm audit
```

The dependency audits report no known advisories at this checkpoint. This does not claim the absence of undiscovered vulnerabilities or replace an OS/image security audit. The release workflow separately gates publication on tests for SQLite and PostgreSQL, browser journeys, concurrency, SMTP failure handling, secret scanning and Docker backup/restore. A published image and a VPS deployment require separate verification.

Dependabot checks weekly, groups minor/patch updates by ecosystem, and keeps major updates separate. Refresh this audit before the next release rather than assuming this snapshot still applies.

## Official sources

- [Node release index](https://nodejs.org/dist/index.json), [PHP releases](https://www.php.net/downloads.php), [Composer](https://getcomposer.org/download/), [PostgreSQL version policy](https://www.postgresql.org/support/versioning/).
- [FrankenPHP releases](https://github.com/php/frankenphp/releases), [PHPUnit 13.4.6](https://github.com/sebastianbergmann/phpunit/releases/tag/13.4.6).
- [npm registry](https://registry.npmjs.org/), including [Vitest](https://registry.npmjs.org/vitest/latest), [concurrently](https://registry.npmjs.org/concurrently/latest) and [Node types](https://registry.npmjs.org/@types/node/latest).
- [Docker base image pinning](https://docs.docker.com/build/building/best-practices/#pin-base-image-versions) and [FrankenPHP image variants](https://frankenphp.dev/docs/docker/).
