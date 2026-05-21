# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2026-05-21

First stable release. Public API surface is now considered locked; changes
that break source compatibility will trigger a major bump.

### Highlights

- **Two authentication modes**: Personal API token (Bearer, recommended)
  and OAuth 1.0a 3-legged (legacy).
- **Full v2 + v4 endpoint coverage** for the platform features most users
  reach for: organisations, applications, add-ons, deployments, environment,
  domains, billing, instances, load balancers, zones, products, drains,
  notifications, webhooks, network groups, operators, pulsar policies,
  orchestration, TCP redirections, backups, API tokens, plus
  `api-bridge.clever-cloud.com` routing.
- **Live log streaming** via Symfony's `EventSourceHttpClient`
  (`Last-Event-ID` resume, transparent reconnection).
- **AutoMapper-driven DTO hydration** — readonly, typed, with `#[MapFrom]`
  for the snake_case ↔ camelCase fields.

### Authentication

- `Credentials` is **abstract**; build credentials via the named constructors:
  - `Credentials::apiToken(string $token)` → `ApiTokenCredentials` —
    `Authorization: Bearer <token>`. Routes every V2/V4 call through
    `api-bridge.clever-cloud.com`. **Recommended.**
  - `Credentials::oauth1(string $consumerKey, string $consumerSecret,
    ?string $token = null, ?string $tokenSecret = null)` →
    `OAuth1Credentials`. HMAC-SHA512 per RFC 5849.
- `ApiVersion::Bridge` + `Configuration::bridgeBaseUrl` for routing.
- `OAuthFlow` helper drives the 3-legged exchange.

### New resources & methods

- **`$client->apiTokens`** (Bridge): `list / get / create / update / delete`.
- **`$client->tcpRedirections`** (V2): `list / get / namespaces / add / remove`.
- **`$client->backups`** (V4): `list / get / restore`.
- **`$client->drains`** (V4): full CRUD + `enable / disable / restart`.
- **`$client->notifications`** (V4): email notification CRUD with event-type
  and service filters.
- **`$client->webhooks`** (V4): `list / create / delete` (raw / slack /
  gitter / flowdock formats).
- **`$client->networkGroups`** (V4): list / get / create / delete + members
  + WireGuard external-peer config download. Operator resources gained
  `linkNetworkGroup()` / `unlinkNetworkGroup()` helpers.
- **`$client->orchestration`** (V4): instances + deployments endpoints.
- **`$client->operators`** (V4): facade routing to Keycloak / Matomo /
  Metabase / Otoroshi with `list / get / create / update / delete / reboot /
  rebuild`.
- **Applications** add `deploy(?string $commit = null)`, `branches()`,
  `instances()`, `dependencies()` + add/remove, `tags()` + add/remove,
  `exposedEnv()` + set, `addons()` + link/unlink.
- **Add-ons** add `sso()`, `tags()` + add/remove, `migrate()`,
  `listMigrations()`, `getMigration()`, `cancelMigration()`,
  `preorderMigration()`.
- **Self** adds `update()`, SSH keys CRUD, email addresses CRUD, OAuth
  consumer CRUD, MFA endpoints, `changePassword()`.
- **Organisations** add OAuth consumers CRUD, `namespaces()`.
- **Domains** add `get(applicationId, fqdn)`.

### Stable enums

Platform-wide values you can drop literal strings for:

- `Flavor` (`pico..3XL`)
- `DeployType` (`git / ftp / docker`)
- `ApplicationState` (full lifecycle + `isStable() / isTransient()`)
- `MigrationStatus` (+ `isTerminal()`)
- Already shipped: `MemberRole`, `DeploymentAction`, `DeploymentState`,
  `DrainType`, `WebhookFormat`.

### Live log streaming

`LogsResource::stream($applicationId, $organisationId, $filters)` returns
an iterable `LogStream<LogEntry>` built on Symfony's
`EventSourceHttpClient` + `ServerSentEvent`. Bearer-auth callers transit
api-bridge; OAuth1 callers transit api.clever-cloud.com. Framing,
reconnection, and `Last-Event-ID` resume are Symfony's responsibility.

### HTTP transport

- `symfony/http-client` (`^8.0`) is now a hard runtime dependency.
  `php-http/discovery` was removed.
- `nyholm/psr7` (`^1.8`) is a runtime dep — used as the default PSR-7 / -17
  implementation.
- `ClientBuilder::withHttpClient()` now takes a
  `Symfony\Contracts\HttpClient\HttpClientInterface`; the SDK wires it
  internally through `Psr18Client` for regular calls and
  `EventSourceHttpClient` for SSE.

### Lifecycle hooks

- `ClientBuilder::onRequest(Closure)` runs after URI/body build, before
  authentication. Return a modified `RequestInterface` to swap it.
- `ClientBuilder::onResponse(Closure)` runs on every response (success +
  error). Read-only.

### Other improvements

- DTO hydration via `jolicode/automapper` — readonly classes, `#[MapFrom]`
  for snake_case API fields.
- Typed exception hierarchy under `CleverCloudException`:
  `ApiException` (+ `AuthException`, `NotFoundException`,
  `ValidationException`, `RateLimitException`, `ServerException`),
  `TransportException`, `ConfigurationException`, `JsonException`. All
  carry `statusCode`, `errorCode`, `requestId`, decoded `body`.
- Retry on 429 (honours `Retry-After`) and 5xx (exponential backoff with
  configurable jitter) via `RetryPolicy`.
- PSR-3 structured logging on channel `clevercloud-sdk`.

### Bug fixes since 0.1.0

- **V4 logs URL** now `/v4/logs/organisations/{owner}/applications/{appId}/logs`
  (was `/v4/logs/{appId}`); owner is required.
- **V4 operators URL** routes through
  `/v4/addon-providers/addon-{kind}/addons[/{id}]` (was the made-up
  `/v4/operators/{kind}/{owner}/...`). Ownership comes from credentials.
- **Pulsar storage policies** hit `/storage-policies` (was `/policy`) and
  use **PATCH** (was PUT). `list()` removed; `delete()` renamed `reset()`.
- **Products endpoints are V2** (not V4) — `ProductsResource` moved to
  `Resource\V2\`. `/v4/products/*` returned 404.
- **`Zone::$tags`** typed as `list<string>` (was `?string`). New `id`,
  `outboundIPs` fields. **`Flavor::$memory`** typed as
  `array<string, mixed>` (the API returns a nested object).
- **Application restart/deploy** now send `{}` JSON body with
  `Content-Type: application/json` so the API doesn't return 415.
- SSE stream errors surface as typed SDK exceptions
  (`AuthException` / `NotFoundException` / `ServerException`) instead of
  silently ending iteration.

### Removed

- `PageIterator` and the entire `src/Pagination/` directory. It was never
  wired into any list endpoint. Cursor pagination will land later when
  Clever Cloud's V4 cursor responses stabilise across endpoints.

### Breaking changes since 0.1.0

- `Credentials` is abstract; replace `new Credentials(...)` with
  `Credentials::oauth1(...)` or `Credentials::apiToken(...)`.
- `ClientBuilder::withHttpClient()` now expects a Symfony
  `HttpClientInterface` (was PSR-18 `ClientInterface`).
- `LogsResource::stream()` signature changes — owner is required, returns
  a Symfony-backed `LogStream`.
- `OperatorsResource` paths and arguments changed.
- `PulsarPoliciesResource`: `list()` removed, `delete()` → `reset()`,
  `update()` uses PATCH.

## [0.1.0] — 2026-05-19

First public preview. PHP 8.5+, PSR-18 + discovery, OAuth 1.0a HMAC-SHA512,
typed DTOs via `jolicode/automapper`, no middleware in the HTTP pipeline.

### Added

- **OAuth 1.0a signer** (`Auth/OAuth1Signer`) implementing RFC 5849 with
  HMAC-SHA512. Pluggable `Psr\Clock\ClockInterface` and `NonceGenerator` for
  deterministic test signatures.
- **HTTP client** (`Http/HttpClient`) with inlined request building, signing,
  retry on 429 (honours `Retry-After`) and 5xx (exponential backoff with
  jitter), and error mapping to typed `ApiException` subclasses.
- **OAuth 3-legged flow helper** (`Auth/OAuthFlow`) for exchanging consumer
  credentials → request token → user token.
- **Exception hierarchy** rooted at `CleverCloudException`:
  `ApiException` (+ `Auth/NotFound/Validation/RateLimit/Server`),
  `TransportException`, `ConfigurationException`, `JsonException`.
- **V2 identity** — `SelfResource`, `UsersResource`, `OrganisationsResource`
  (+ `User`, `Organisation`, `Member` DTOs and `MemberRole` enum).
- **V2 workloads** — `ApplicationsResource`, `AddonsResource`,
  `DeploymentsResource`, `EnvironmentResource`, `DomainsResource`
  (+ `Application`, `Vhost`, `Addon`, `AddonProvider`, `AddonPlan`,
  `Deployment`, `DeploymentAction`, `DeploymentState`).
- **V4 platform** — `BillingResource`, `InstancesResource`,
  `LoadBalancersResource`, `ProductsResource`, `ZonesResource`,
  `PulsarPoliciesResource` (+ DTOs: `Invoice`, `PaymentMethod`,
  `Consumption`, `InstanceType`, `Flavor`, `Zone`, `Country`,
  `LoadBalancer`, `PulsarPolicy`).
- **V4 logs streaming** — `LogsResource::stream/query`, `LogStream`,
  `SseStream`, `SseEvent` (+ `LogEntry` DTO).
- **V4 operators** — `OperatorsResource` facade routing to
  `KeycloakResource`, `MatomoResource`, `MetabaseResource`,
  `OtoroshiResource` (+ shared `Operator` DTO).
- **Pagination skeleton** — `PageIterator` for cursor-based v4 endpoints.
- **Smoke examples** — `examples/smoke-self.php` and `examples/stream-logs.php`.

[Unreleased]: https://github.com/welcoMattic/clevercloud-php-sdk/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/welcoMattic/clevercloud-php-sdk/compare/v0.1.0...v1.0.0
[0.1.0]: https://github.com/welcoMattic/clevercloud-php-sdk/releases/tag/v0.1.0
