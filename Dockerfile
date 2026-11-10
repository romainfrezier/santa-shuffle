FROM --platform=$BUILDPLATFORM node:26.13.0-trixie-slim@sha256:5adad64f9a1fca2d51972caa65983634c8a774eb300d72b20f7f2a0f60368072 AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install --global "$(node -p 'require("./package.json").packageManager')" && npm ci
COPY vite.config.js tsconfig.json ./
COPY resources ./resources
COPY playwright.config.ts ./
COPY tests/browser ./tests/browser
COPY vitest.config.ts ./
COPY tests/frontend ./tests/frontend
COPY scripts ./scripts
COPY docs/licenses ./docs/licenses
RUN npm run typecheck && npm run build

FROM dunglas/frankenphp:1.13.4-php8.5-trixie@sha256:6f86f003f1478bafa7befe6ec26db5575e703882171f785189de1e222df0e38e AS php-base
RUN install-php-extensions pdo_pgsql pdo_sqlite intl zip pcntl
COPY --from=composer:2.11.1@sha256:5190d9b445add2fa26090108273cb1ef1e1af98175201c737b85b3c4f90fbf7c /usr/bin/composer /usr/bin/composer
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
