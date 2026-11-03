# Container releases

The release workflow publishes to `ghcr.io/romainfrezier/santa-shuffle` (derived from `github.repository`, so forks publish under their own owner) using the repository's built-in `GITHUB_TOKEN`. It does not require a personal access token. No repository, tag or container has been published merely by adding these files.

## One-time repository setup

1. Push the reviewed source and lockfiles to [the canonical repository](https://github.com/romainfrezier/santa-shuffle). It was initially created as a private, empty repository; making it public is a separate owner decision. Set its description, topics, license and default branch.
2. Enable Actions and allow the release job to write packages. Organization policy may require additional approval. Enable private vulnerability reporting and publish a monitored maintainer contact.
3. Protect the default branch with pull-request review and required checks. Restrict creation, update and deletion of `v*` tags to maintainers using a repository ruleset. Never move an existing release tag.
4. After the first successful publication, open the package's settings and set its visibility to **Public**. A public source repository does not automatically make the container public. If the package already exists, grant this repository Actions access to it.
5. Verify an unauthenticated pull of the exact published version and digest from a clean environment. The workflow summary records the digest; successful YAML validation alone does not prove publication.

## Publish a release

Choose a reviewed commit and a version according to Semantic Versioning. Breaking behavior or incompatible data changes require an explicit upgrade note. At version `0.x`, document any incompatible change clearly.

```sh
git tag -a v0.1.0 -m "Release v0.1.0"
git push origin v0.1.0
```

Pushing `vMAJOR.MINOR.PATCH` or a prerelease such as `v0.2.0-rc.1` triggers `.github/workflows/release.yml`. Invalid version tags fail before publication; build metadata (`+...`) is intentionally unsupported because it is not a valid Docker tag character.

The workflow runs the reusable full CI suite first: PHP on SQLite/PostgreSQL, frontend unit tests, lint/type checks, dependency audits, Chromium journeys, runtime races/SMTP, secret scanning and isolated Docker restore checks. Only a successful gate can publish. The publish job alone has `packages: write`, and third-party actions are pinned to commit SHAs with Dependabot updates.

Images target `linux/amd64` and `linux/arm64`. QEMU supports the cross-platform build; CI runtime tests currently execute on the GitHub runner architecture, so a successful cross-build is not an ARM runtime test. The image includes OCI source/revision/license metadata, BuildKit provenance and an SBOM. These are build evidence, not a guarantee of vulnerability-free dependencies or a separate signature-verification policy.

Every release gets its full version tag, including the leading `v`. Stable releases also update `latest`; prereleases do not. `latest` means the most recently published stable tag, not the numerically highest version: do not publish an older stable tag after a newer one if that would move `latest` backward. Deploy explicit versions or digests. The workflow does not create GitHub release notes or deploy a VPS.

## Deploy the published image

Copy `.env.docker.example` to `.env.docker`, set `SANTA_IMAGE=ghcr.io/romainfrezier/santa-shuffle:v0.1.0` (prefer `@sha256:...` for immutable deployment), and configure the app key, SMTP and HTTPS settings. Keep the same `APP_KEY` for an existing installation.

```sh
docker compose --env-file .env.docker pull app worker scheduler
# For a new installation only: copy this output into APP_KEY privately.
docker compose --env-file .env.docker run --rm --no-deps --entrypoint php app artisan key:generate --show
docker compose --env-file .env.docker run --rm --no-deps app php artisan migrate --force
docker compose --env-file .env.docker up -d --no-build
```

For PostgreSQL, add `-f compose.yaml -f compose.postgres.yaml` to **every** command and start the database before migrations. For upgrades, back up the database, storage and private configuration, stop app/worker/scheduler, pull the new image, migrate explicitly, then start all three services together. Restore the matching image and backup together if a schema rollback is unsafe. Never use `down -v` on data you intend to keep.

## Sources

- [GitHub: publish Docker images](https://docs.github.com/en/actions/tutorials/publish-packages/publish-docker-images)
- [GitHub: package access and visibility](https://docs.github.com/en/packages/learn-github-packages/configuring-a-packages-access-control-and-visibility)
- [Docker: multi-platform GitHub Actions builds](https://docs.docker.com/build/ci/github-actions/multi-platform/)
