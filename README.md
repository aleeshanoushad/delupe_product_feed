# Delupe Product Feed Service

A Laravel-based REST API service that imports, validates, stores, and exposes merchant product data. The application is fully containerized with Docker and PostgreSQL.

## Features

- Import products from JSON files via CLI
- Import large JSON files via queued batch jobs
- Validate product records (name, price, ISO currency)
- Update existing products on re-import
- Bulk price adjustments with original price preservation
- REST API with pagination and filtering
- Product summary and duplicate detection endpoints
- API key authentication
- Structured logging for import events
- Health check endpoint
- Automated tests and CI pipeline

## Tech Stack

- **Framework:** Laravel 13
- **Database:** PostgreSQL 18
- **Containerization:** Docker & Docker Compose
- **Testing:** PHPUnit
- **Static Analysis:** PHPStan
- **Code Style:** Laravel Pint

## Quick Start

### Prerequisites

- Docker
- Docker Compose

### Start the Application

```bash
docker compose up -d --build
```

The API will be available at `http://localhost:8000`.

On first startup, the container automatically:
1. Copies `.env.example` to `.env` (if needed)
2. Generates an application key
3. Waits for PostgreSQL to be ready
4. Runs database migrations

**Verify the setup:**

```bash
curl http://localhost:8000/health
```

Expected response:

```json
{"status":"ok","database":"connected"}
```

### Stop the Application

```bash
docker compose down
```

To remove persistent database data:

```bash
docker compose down -v
```

## Environment Configuration

Copy `.env.example` to `.env` and adjust as needed:

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | PostgreSQL host | `postgres` |
| `DB_DATABASE` | Database name | `delupe_db` |
| `DB_USERNAME` | Database user | `del` |
| `DB_PASSWORD` | Database password | `delupe123` |
| `QUEUE_CONNECTION` | Default Laravel queue connection | `database` |
| `API_KEY` | API authentication key | `your_api_key1` |

## Database Migrations

Migrations run automatically on container startup. To run manually:

```bash
docker compose exec app php artisan migrate
```

To refresh the database:

```bash
docker compose exec app php artisan migrate:fresh
```

## Product Import

A sample `products.json` file is included in the project root.

### Import Commands

For normal imports, use the synchronous CLI command:

```bash
docker compose exec app php artisan app:import-products products.json
```

For large files, use the queued batch import command:

```bash
docker compose exec app php artisan app:import-products-batch products.json
```

> **Note:** Laravel uses `php artisan` instead of Symfony's `php bin/console`. The normal import command is `app:import-products`, and the batch import command is `app:import-products-batch`.

The normal import command will:
- Read products from the JSON file
- Validate each record
- Insert new products
- Update existing products (matched by `id`)
- Log validation errors
- Display an import summary

The batch import command will:
- Dispatch one queued `ImportProductJob` per record
- Use Laravel job batching for large JSON payloads
- Store progress and validation outcomes in the queue batch
- Allow processing to continue in the background via a queue worker

To process queued jobs, run a queue worker in the app container:

```bash
docker compose exec app php artisan queue:work --sleep=3 --tries=3
```

If you use the default database queue connection, the required `jobs`, `job_batches`, and `failed_jobs` tables are available via migrations.

### Sample JSON Format

```json
[
  {
    "id": 1,
    "merchant_id": "merchant_001",
    "name": "Wireless Bluetooth Headphones",
    "link": "https://shop.example.com/products/wireless-headphones",
    "image_link": "https://shop.example.com/images/headphones.jpg",
    "price": 79.99,
    "currency": "EUR"
  }
]
```

### Validation Rules

- Product name cannot be empty
- Price must be greater than zero
- Currency must be a valid ISO currency code (EUR, USD, GBP, etc.)
- Product ID, merchant ID, link, and image link are required

## Price Adjustment

Apply a percentage adjustment to all product prices:

```bash
docker compose exec app php artisan app:update-prices 10
```

This increases all prices by 10%. Use a negative value to decrease prices (e.g., `-5` for a 5% reduction).

The command preserves the original price in the `original_price` field before applying the adjustment.

## REST API

All API endpoints (except health check) require the `X-API-Key` header.

### Authentication

```
X-API-Key: your_api_key
```

### List Products

```
GET /api/products?page=1&limit=50&currency=EUR&min_price=100&max_price=500
```

**Query Parameters:**

| Parameter | Description |
|-----------|-------------|
| `page` | Page number (default: 1) |
| `limit` | Items per page (default: 50, max: 100) |
| `currency` | Filter by ISO currency code |
| `min_price` | Minimum price filter |
| `max_price` | Maximum price filter |

**Example:**

```bash
curl -H "X-API-Key: your_api_key" \
  "http://localhost:8000/api/products?page=1&limit=50&currency=EUR&min_price=100&max_price=500"
```

### Product Summary

```
GET /api/products/summary
```

**Response:**

```json
{
  "count": 1000,
  "total_price": 45670.00,
  "average_price": 45.67,
  "currencies": {
    "EUR": 500,
    "USD": 500
  }
}
```

**Example:**

```bash
curl -H "X-API-Key: your_api_key" http://localhost:8000/api/products/summary
```

### Duplicate Products

Returns products that share the same name or link.

```
GET /api/products/duplicates
```

**Example:**

```bash
curl -H "X-API-Key: your_api_key" http://localhost:8000/api/products/duplicates
```

### Health Check

No authentication required.

```
GET /health
```

**Response:**

```json
{
  "status": "ok",
  "database": "connected"
}
```

**Example:**

```bash
curl http://localhost:8000/health
```

## Running Queue Worker

For batch imports to process in the background, run a queue worker:

```bash
docker compose exec app php artisan queue:work --sleep=3 --tries=3
```

Queue worker options:
- `--sleep=3`: Seconds to sleep when no jobs are available
- `--tries=3`: Number of times to attempt a failed job before moving to failed queue

To retry failed jobs:

```bash
docker compose exec app php artisan queue:retry all
```

To view failed jobs:

```bash
docker compose exec app php artisan queue:failed
```

## Running Tests

```bash
docker compose exec app php artisan test
```

Or locally (requires PHP 8.4+ and Composer):

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
```

### Test Coverage

- Product import (insert, update, validation failures)
- Validation logic (name, price, currency rules)
- Product summary API endpoint
- API key authentication
- Product filtering
- Health check endpoint

## Logging

Import events are logged using Laravel's standard logging mechanism:

- Import started
- Import completed (with success/failure counts)
- Validation errors per record

Logs are stored in `storage/logs/laravel.log`.

## CI/CD

GitHub Actions workflow (`.github/workflows/ci.yml`) runs on every push:

- PHPUnit tests
- PHPStan static analysis
- Laravel Pint coding standards check

## Project Structure

```
app/
├── Console/Commands/     # CLI commands (import, price update)
│   ├── ImportProducts.php        # Normal synchronous import
│   └── ImportProductsBatch.php   # Queued batch import
├── Http/
│   ├── Controllers/Api/  # REST API controllers
│   └── Middleware/       # API key authentication
├── Jobs/
│   └── ImportProductJob.php      # Queued job for batch import
├── Models/               # Eloquent models
└── Services/             # Business logic (import, validation, pricing)
database/migrations/      # Database schema (includes jobs, job_batches, failed_jobs)
docker/                   # Docker entrypoint scripts
routes/api.php            # API routes
tests/                    # PHPUnit tests
products.json             # Sample import data (small)
products_large.json       # Sample import data (large, for batch testing)
```

## Troubleshooting

### Database Connection Issues

If you see "connection refused" errors:

```bash
# Check if PostgreSQL is running
docker compose ps

# View app container logs
docker compose logs app

# View database container logs
docker compose logs postgres
```

### Migrations Failed

To manually retry migrations:

```bash
docker compose exec app php artisan migrate:fresh
```

**Warning:** This will drop all tables and reseed. Use only in development.

### Queue Jobs Not Processing

If batch imports aren't progressing:

1. Verify a queue worker is running:
   ```bash
   docker compose exec app php artisan queue:work
   ```

2. Check for failed jobs:
   ```bash
   docker compose exec app php artisan queue:failed
   ```

3. View queue table:
   ```bash
   docker compose exec app php artisan queue:clear
   ```

### Clear Application Cache

If experiencing unexpected behavior:

```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```

## License

MIT
