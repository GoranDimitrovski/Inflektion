.DEFAULT_GOAL := help
.PHONY: help setup up down build install build-web test test-file phpstan pint pint-fix \
        generate-client check-openapi check-client audit web-test \
        artisan shell-app shell-web ci serve serve-web queue-work schedule-work dev

APP := docker compose run --rm app
WEB := docker compose run --rm web sh -c

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

setup: ## Set up the whole project from scratch: build, install deps, generate key, migrate
	@test -f .env || cp .env.example .env
	@test -f api/.env || cp api/.env.example api/.env
	docker compose build
	docker compose up -d postgres redis
	$(APP) composer install
	$(WEB) "pnpm install --frozen-lockfile"
	$(APP) php artisan key:generate
	$(APP) php artisan migrate
	docker compose up -d app web
	@echo "Setup complete. API: http://localhost:8000  Web: http://localhost:4200"

up: ## Start postgres/redis in the background
	docker compose up -d postgres redis

serve: ## Start the app as a running server (needed for the API docs UI)
	docker compose up -d app
	@echo "API docs: http://localhost:8000/docs/api"

serve-web: ## Start the Angular dev server in the background (http://localhost:4200)
	docker compose up -d web

queue-work: ## Start the queue worker in the background (consumes ShouldQueue jobs)
	docker compose up -d queue

schedule-work: ## Start the scheduler in the background (runs outbox:publish etc. on schedule)
	docker compose up -d scheduler

dev: serve serve-web queue-work schedule-work ## Start the app, web dev server, queue worker, and scheduler together

down: ## Stop and remove all containers
	docker compose down

build: ## Build the app and web images
	docker compose build

install: ## Install PHP and JS dependencies
	$(APP) composer install
	$(WEB) "pnpm install --frozen-lockfile"

build-web: ## Build the Angular app in the web container (output in web/dist)
	$(WEB) "pnpm run build"

test: ## Run the PHPUnit suite
	$(APP) ./vendor/bin/phpunit

test-file: ## Run one test file, e.g. make test-file FILE=tests/Unit/MoneyTest.php
	$(APP) ./vendor/bin/phpunit $(FILE)

phpstan: ## Run PHPStan (Larastan level 6)
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=512M

pint: ## Check code style without fixing
	$(APP) ./vendor/bin/pint --test

pint-fix: ## Fix code style
	$(APP) ./vendor/bin/pint

generate-client: ## Regenerate openapi.json and the Angular API client from it
	$(APP) php artisan scramble:export --path=../openapi.json
	$(WEB) "pnpm run generate:client"

check-openapi: ## Fail if openapi.json is out of date (as CI does)
	$(APP) php artisan scramble:export --path=../openapi.json
	git diff --exit-code -- openapi.json

check-client: ## Fail if the generated Angular API client is out of date (as CI does)
	$(WEB) "pnpm run generate:client"
	git diff --exit-code -- web/src/app/api

audit: ## Run composer audit
	$(APP) composer audit

web-test: ## Run the Angular unit tests
	$(WEB) "pnpm exec ng test --watch=false"

artisan: ## Run an artisan command, e.g. make artisan CMD="migrate:fresh"
	$(APP) php artisan $(CMD)

shell-app: ## Open a shell in the app container
	docker compose run --rm app sh

shell-web: ## Open a shell in the web container
	docker compose run --rm web sh

ci: pint phpstan test audit check-openapi check-client build-web web-test ## Run the same checks CI runs
