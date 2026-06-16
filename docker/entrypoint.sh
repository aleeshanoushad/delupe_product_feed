#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Ensure Docker environment variables are written to .env for artisan serve
for var in DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD API_KEY; do
    eval "value=\$$var"
    if [ -n "$value" ]; then
        if grep -q "^${var}=" .env; then
            sed -i "s|^${var}=.*|${var}=${value}|" .env
        else
            echo "${var}=${value}" >> .env
        fi
    fi
done

echo "Waiting for database..."
for i in $(seq 1 30); do
    if php artisan migrate --force 2>/dev/null; then
        break
    fi
    echo "Database not ready, retrying in 2s... ($i/30)"
    sleep 2
done

exec php artisan serve --host=0.0.0.0 --port=8000
