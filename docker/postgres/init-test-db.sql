-- Runs once, on first container init, alongside the main POSTGRES_DB.
-- Keeps local `php artisan test` runs off the dev database so test data
-- (wrapped in RefreshDatabase transactions or not) never touches it.
CREATE DATABASE inflection_test;
