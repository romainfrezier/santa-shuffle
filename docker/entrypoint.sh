#!/bin/sh
set -eu
if [ -z "${APP_KEY:-}" ] && [ -z "${APP_KEY_FILE:-}" ]; then
    echo "Set APP_KEY or APP_KEY_FILE before starting Santa Shuffle." >&2
    exit 1
fi
if [ -n "${APP_KEY_FILE:-}" ] && [ ! -s "$APP_KEY_FILE" ]; then
    echo "APP_KEY_FILE must be a readable non-empty file." >&2
    exit 1
fi
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi
exec "$@"
