# Architecture

Inflection is a multi-tenant affiliate/referral tracking and commissions platform: an
account creates trackable **links** for one or more **programs**, referred traffic
generates **clicks**, a storefront reports back **conversions**, conversions are
**attributed** to the click/program that produced them, and attributed conversions
generate **commission ledger entries** that get grouped into **payout batches**.

## Stack

| Layer | Choice |
|---|---|
| API | Laravel 13, PHP 8.4, [`laravel-json-api/laravel`](https://laraveljsonapi.io/) for JSON:API endpoints |
| Auth | Sanctum (session + API tokens), Fortify (2FA only) |
| API docs | [Scramble](https://scramble.dedoc.co/) generates `openapi.json` from the controllers |
| Database | PostgreSQL 17 |
| Cache / queue | Redis 7 |
| Front end | Angular 22, standalone components, generated API client (`@hey-api/openapi-ts`) |
| Local dev | Docker Compose (`app`, `web`, `queue`, `scheduler`, `postgres`, `redis`) |

## Directory map (`api/app/`)

```
api/app/
├── Access/             Role, PermissionMap, TenantContext, AuditEntry, ApiToken
├── Actions/            One class per use case, grouped by domain
│   ├── Access/         invite/accept/change-role/revoke/issue-token/disable-2FA
│   ├── Attribution/    accept postback, attribute conversion to click/program
│   ├── Commerce/       create program, open/close payout batch, write commission
│   └── Tracking/       record a click
├── Commissions/        CommissionStrategy interface + registry (Flat, Percentage)
├── Personalization/    PersonalizationStrategy interface + registry (Null, Weighted)
├── Integrations/
│   └── Storefronts/    StorefrontAdapter interface + registry, one per vendor
├── JsonApi/V1/         Schema/Request/Query classes per JSON:API resource
├── Http/Controllers/   Thin controllers: JSON:API CRUD, Auth/, Attribution/, Tracking/
├── Models/             Eloquent models; Concerns/BelongsToTenant for tenant scoping
├── Events/Listeners/   Domain events + their queued listeners
├── Support/            Money value object + MoneyCast, Clock, Outbox/ (transactional outbox)
├── Console/Commands/   outbox:publish (scheduled)
└── Policies/           One policy per JSON:API resource; Concerns/ChecksPermission
```

## Domain pipeline

A referral link click is recorded and redirects to the destination, with a
personalization variant applied along the way. When the storefront later reports a
conversion, it's matched back to that click, attributed to the right account and
program, and a commission is calculated (pluggable per program — flat or
percentage) and recorded as money, never a float. Commissions get grouped into
payout batches for finance to pay out. Every domain event is queued through an
outbox first, so a failed listener never loses the underlying business fact.

## Multi-tenancy and access control

Every account-scoped request resolves the account and checks the caller is a
member of it — if not, it returns a plain 404 rather than a 403, so an account's
existence is never revealed to a non-member. A token issued for one account can't
be used against another, even by the same user. Once resolved, every database
query is automatically scoped to that account, so application code never has to
filter by account manually.

Roles are ranked (Owner > Admin > Member > Viewer) and mapped to fine-grained
permissions. Every permission check verifies both the caller's role and, if they
authenticated with an API token, that the token was granted that permission — a
token can never do more than the role that issued it could.

## Auth

- **Session (SPA)** — Sanctum cookie auth; `GET /sanctum/csrf-cookie` primes
  `XSRF-TOKEN`, echoed back as `X-XSRF-TOKEN` on writes (`app.config.ts` interceptor)
- **API tokens** — `ApiToken` extends Sanctum's `PersonalAccessToken`, scoped to one
  account, capped to the issuing role's permissions
- **2FA** — Fortify, 2FA routes only (`Fortify::ignoreRoutes()`); if
  `two_factor_confirmed_at` is set, login returns `{ twoFactorRequired: true }` and
  the session isn't created until `POST /two-factor-challenge` succeeds

## Actions and audit trail

- Business logic lives in single-purpose `handle()` action classes
  (`app/Actions/**`), each in one DB transaction, called directly from
  controllers/listeners — no service or repository layer
- Every action that changes access or money appends an `AuditEntry` in the same
  transaction (append-only — `update()`/`delete()` throw, enforced by an arch test),
  e.g. `auth.login`, `access.token_revoked`, `commerce.payout_batch_closed`

## JSON:API resources

- `api/v1/accounts/{account}/{resource}` (programs, invitations, memberships,
  api-tokens), served by `laravel-json-api/laravel`
- A `Schema` class declares fields/filters; a package trait handles JSON:API
  request/response mechanics; a thin controller method calls the matching Action
- Response shapes are documented via `@response` PHPDoc on controller methods —
  Scramble parses these to generate `openapi.json`, the sole input to the Angular
  client, so removing them silently breaks client types with no test catching it

## Front end

- Angular 22, standalone components, feature folders
  (`web/src/app/features/{auth,programs,invitations,members}`)
- `web/src/app/api` is **entirely generated** from `openapi.json`
  (`pnpm run generate:client`) — never hand-edit it
- `SessionService` (`core/session.ts`) owns login/logout/CSRF bootstrapping;
  `app.config.ts` wires the client's headers and CSRF/401-redirect interceptors

## Background processes

Beyond `app` (HTTP) and `web` (Angular dev server), `docker-compose.yml` runs:

- `queue` — `php artisan queue:work`, consumes `ShouldQueue` listeners
- `scheduler` — `php artisan schedule:work`, runs `outbox:publish`

## Testing and static analysis

- **api/** — PHPUnit (feature + unit, incl. tenant-isolation and audit arch tests),
  PHPStan/Larastan level 6, Pint (Laravel preset)
- **web/** — Vitest (`ng test`), `tsc --noEmit`, ESLint (`ng lint`)
- **CI** additionally fails the build if `openapi.json` or the generated Angular
  client are stale — regenerate and commit both after changing a controller's
  routes/request/response shape
