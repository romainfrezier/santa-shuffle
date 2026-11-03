FROM --platform=$BUILDPLATFORM node:24-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tsconfig.json ./
COPY resources ./resources
COPY playwright.config.ts ./
COPY tests/browser ./tests/browser
COPY vitest.config.ts ./
COPY tests/frontend ./tests/frontend
COPY scripts ./scripts
COPY docs/licenses ./docs/licenses
RUN npm run typecheck && npm run build

FROM dunglas/frankenphp:1-php8.5-trixie AS php-base
RUN install-php-extensions pdo_pgsql pdo_sqlite intl zip pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM php-base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative

FROM php-base AS runtime
LABEL org.opencontainers.image.title="Santa Shuffle" \
      org.opencontainers.image.description="A self-hosted, multilingual Secret Santa for your team" \
      org.opencontainers.image.licenses="MIT"
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr SERVER_NAME=:8080
COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build /app/public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/santa-entrypoint
RUN mkdir -p /app/storage/database /data/caddy /config/caddy     && chown -R www-data:www-data /app/storage /app/bootstrap/cache /data /config
USER www-data
EXPOSE 8080
ENTRYPOINT ["santa-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
