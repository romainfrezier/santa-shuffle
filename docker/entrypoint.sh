#!/bin/sh
set -eu
if [ -z "${APP_KEY:-}" ]; then
    echo "Set APP_KEY in .env.docker before starting Santa Shuffle." >&2
    exit 1
fi
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi
exec "$@"
