# Inflection

An affiliate/referral tracking and commissions platform: a Laravel API (`api/`) with an
Angular front end (`web/`), running on PostgreSQL and Redis.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the domain model, request flows, and
multi-tenancy/RBAC details.

## Prerequisites

[Docker](https://www.docker.com/) with Docker Compose

## Setup (with `make`)

```bash
make setup
```

Builds the images, installs dependencies, generates the app key, runs migrations,
seeds the database, and starts the API + web dev server (same result as the no-`make`
steps below). Run `make help` to see all available targets.

The seed creates a ready-to-use login: `owner@example.test` / `password` (Owner role
on an "Example Co" account).

## Setup (Docker, no `make` required)

**Copy environment files**

```bash
cp .env.example .env
cp api/.env.example api/.env
```

**Build the images**

```bash
docker compose build
```

**Start the database and cache**

```bash
docker compose up -d postgres redis
```

**Install dependencies**

```bash
docker compose run --rm app composer install
docker compose run --rm web sh -c "pnpm install --frozen-lockfile"
```

**Generate the app key, run migrations, and seed the database**

```bash
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
```

The seed creates a ready-to-use login: `owner@example.test` / `password` (Owner role
on an "Example Co" account). It's safe to re-run at any time.

**Start the API and web dev server**

```bash
docker compose up -d app web
```

API: http://localhost:8000 · Web (hot reload): http://localhost:4200

**Run background jobs and the scheduler** (optional)

```bash
docker compose up -d queue scheduler
```

**Stop everything**

```bash
docker compose down
```

## Everyday commands

**Run any artisan command**

```bash
docker compose run --rm app php artisan <command>
```

**Run PHP tests**

```bash
docker compose run --rm app ./vendor/bin/phpunit
```

**Fix PHP code style**

```bash
docker compose run --rm app ./vendor/bin/pint
```

**Run static analysis**

```bash
docker compose run --rm app ./vendor/bin/phpstan analyse --memory-limit=512M
```

**Run Angular unit tests**

```bash
docker compose run --rm web sh -c "pnpm exec ng test --watch=false"
```

**Production Angular build** (output in `web/dist`)

```bash
docker compose run --rm web sh -c "pnpm run build"
```

**Regenerate the API client** after changing an endpoint's routes/request/response shape

```bash
docker compose run --rm app php artisan scramble:export --path=../openapi.json  # api/ -> openapi.json
docker compose run --rm web sh -c "pnpm run generate:client"                    # openapi.json -> web client
```

## CI

`.github/workflows/ci.yml` runs on every push/PR to `main`:

- **API** — Pint, PHPStan, PHPUnit, `composer audit`, and checks `openapi.json` is up to date.
- **Docker** — builds both images and smoke-tests the API and web dev server.
- **Web** — checks the generated API client is up to date, builds, and runs unit tests.

## Project layout

- `api/` — Laravel application (see [ARCHITECTURE.md](ARCHITECTURE.md))
- `web/` — Angular application
- `openapi.json` — OpenAPI spec generated from `api/` (via [Scramble](https://scramble.dedoc.co/));
  source of truth for the Angular API client in `web/src/app/api`
- `docker-compose.yml` / `Makefile` / `docker/` — local dev environment
