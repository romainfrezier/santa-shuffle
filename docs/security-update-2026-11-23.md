# Security update, 2026-11-23

Production was inspected read-only and runs Laravel 13.40.0, PHP 8.5.12 and FrankenPHP 1.13.4 in image v0.1.1. The prepared candidate uses Laravel 13.43.0, PHP 8.5.13, FrankenPHP 1.14.0, Composer 2.11.2, Node 26.13.1 and npm 12.2.3. PostgreSQL remains on major 18 and is pinned to 18.7. Docker image references include manifest digests. Debian packages are upgraded during image construction.

Laravel 13.43.0 fixes the November advisories affecting 13.40.0, including validation and signed-URL bypasses. The crafted-JSON advisory GHSA-8h64-cpq2-9h2g was already fixed in 13.38.0. Composer and npm audits returned zero advisories, including development dependencies. Registry audits alone did not report the newly published Laravel advisories.

Local checks passed for 72 PHP tests on isolated SQLite and PostgreSQL 18.7, 12 frontend unit tests, static analysis, formatting, type checking and production assets. The browser suite passed all 11 journeys after installing its matching Chromium. Runtime races and SMTP recovery were also exercised.

The image scan additionally reports distribution findings without a published Trixie fix and three go-jose v3 findings embedded in FrankenPHP. No blanket suppression was added. CVE-2026-6653 is classified critical by the scanner; Debian tracks it as a minor libxml2 issue with no DSA for Trixie. This application accepts JSON/forms and has no untrusted XML parsing feature. The stock Caddyfile enables no JOSE authentication module. These observations narrow the identified application exposure and do not claim that the base image is vulnerability-free.

Full raw image findings are retained locally in ignored artifacts/security-image-final.json. Recheck against the exact published AMD64 image before production rollout. The candidate is local and requires publication and an explicitly authorized deployment. Preserve the existing storage bind and APP_KEY, take a fresh online checkpoint, and keep the previous image digest for code rollback. There are no schema changes in this update.

Sources:
- https://github.com/laravel/framework/security/advisories
- https://www.php.net/ChangeLog-8.php
- https://frankenphp.dev/docs/docker/
- https://security-tracker.debian.org/tracker/CVE-2026-6653

The final ARM64 image was built and reports PHP 8.5.13, Laravel 13.43.0 and Caddy 2.11.9. Updating Debian removed 311 scanner findings with available fixes. The final scan retains 1,413 package/advisory occurrences (1 critical, 87 high, 447 medium, 851 low, 27 unknown), all without a fixed version in the scanner feed. These counts include build tools inherited from the PHP base. They require package-level review before claiming comprehensive remediation. The full JSON preserves every finding; none were suppressed.
