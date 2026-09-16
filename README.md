# Inflection

A Laravel API (`api/`) with an Angular front end (`web/`), running on PostgreSQL and Redis.

## Prerequisites

- [Docker](https://www.docker.com/) with Docker Compose
- `make` (optional — everything below also has a plain Docker Compose equivalent)

## With `make`

One command builds the images, installs PHP/JS dependencies, generates the app key, runs migrations, and starts everything:

```bash
make setup
```

That's it — the API is running at http://localhost:8000 and the Angular dev server (with hot reload) at http://localhost:4200.

Run `make help` to see every other target (`test`, `phpstan`, `pint`, `queue-work`, `dev`, `ci`, `build-web`, ...).

## Without `make`

Everything a `make` target does is a thin wrapper around Docker Compose. Run the equivalent commands directly:

```bash
# copy env files (api/.env must exist before "artisan key:generate" can write to it)
cp .env.example .env
cp api/.env.example api/.env

# build the app and web images
docker compose build

# start postgres + redis
docker compose up -d postgres redis

# install dependencies
docker compose run --rm app composer install
docker compose run --rm web sh -c "pnpm install --frozen-lockfile"

# set up the app
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate

# run the API and the Angular dev server (with hot reload)
docker compose up -d app web
# API docs: http://localhost:8000/docs/api
# Web app:  http://localhost:4200

# run the queue worker / scheduler (optional)
docker compose up -d queue
docker compose up -d scheduler
```

Other useful commands:

```bash
docker compose run --rm web sh -c "pnpm run build"                # production build, output in web/dist
docker compose run --rm app ./vendor/bin/phpunit                  # tests
docker compose run --rm app ./vendor/bin/phpunit tests/Unit/MoneyTest.php  # one test file
docker compose run --rm app ./vendor/bin/phpstan analyse --memory-limit=512M  # static analysis
docker compose run --rm app ./vendor/bin/pint --test              # style check
docker compose run --rm app ./vendor/bin/pint                     # style fix
docker compose run --rm app sh                                    # shell in the app container
docker compose run --rm web sh                                    # shell in the web container
docker compose down                                                 # stop everything
```

## Project layout

- `api/` — Laravel application (see `api/README.md` for framework-level notes)
- `web/` — Angular application (see `web/README.md` for framework-level notes)
- `docs/adr/` — architecture decision records
- `openapi.json` — generated OpenAPI spec, source for the Angular API client
- `docker-compose.yml` / `Makefile` — local dev environment
