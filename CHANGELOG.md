# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added — v1.0 prep

- **API token (Bearer) authentication** — `Credentials::apiToken()` mints an
  `ApiTokenCredentials` that attaches `Authorization: Bearer <token>`. The
  legacy OAuth1 path is preserved as `Credentials::oauth1()` returning
  `OAuth1Credentials`. `Credentials` is now abstract; instantiation goes
  through the named constructors.
- **`ApiVersion::Bridge`** enum case + `Configuration::bridgeBaseUrl`
  routing endpoints to `https://api-bridge.clever-cloud.com`.
- **`$client->apiTokens`** — V4 / api-bridge CRUD for personal API tokens
  (`list`, `get`, `create`, `update`, `delete`). New `ApiToken` DTO.
- **`$client->tcpRedirections`** — V2 TCP port redirections per app, plus a
  `namespaces()` helper. New `TcpRedirection` DTO.
- **`$client->backups`** — V4 add-on backups (`list`, `get`, `restore`).
  New `Backup` DTO.
- **Application deploy/branches** — `ApplicationsResource::deploy(commit?)`
  and `::branches()`.
- **Domain get** — `DomainsResource::get(appId, fqdn)`.
- **Add-on migrations** — `listMigrations()`, `getMigration()`,
  `cancelMigration()`, `preorderMigration()` complete the `migrate()` start
  endpoint.
- **Lifecycle hooks** — `ClientBuilder::onRequest()` / `onResponse()`
  callbacks invoked around every dispatch.

### Changed — v1.0 prep

- **`AbstractResource`** is now marked `@internal`. The class hierarchy is
  used by the SDK's own V2/V4/Bridge subclasses; user code should not extend it.
- README: explicit coverage matrix per family; PSR-3 log-key reference;
  `MockHttpClient` testing pattern documented.

### Removed — v1.0 prep

- **`PageIterator`** skeleton has been removed. Pagination was never wired
  to any resource — it was dead code. List endpoints continue to return
  `array<T>`; cursor pagination will land post-1.0 when needed.

### Added

- **V2 application extensions** — `dependencies()`, `addDependency()`,
  `removeDependency()`, `tags()` / `addTag()` / `removeTag()`,
  `exposedEnv()` / `setExposedEnv()`, `addons()` / `linkAddon()` /
  `unlinkAddon()` on `ApplicationsResource`.
- **V2 add-on extensions** — `sso()`, `tags()` / `addTag()` /
  `removeTag()`, `migrate()` on `AddonsResource`.
- **V2 self management** — `update()`, `sshKeys()` / `addSshKey()` /
  `removeSshKey()`, `emailAddresses()` / `addEmailAddress()` /
  `removeEmailAddress()`, OAuth consumer CRUD (`consumers()`,
  `getConsumer()`, `createConsumer()`, `updateConsumer()`,
  `deleteConsumer()`), MFA endpoints (`startMfa()`, `confirmMfa()`,
  `disableMfa()`, `regenerateMfaBackupCodes()`), `changePassword()`.
  New DTOs: `SshKey`, `EmailAddress`, `OAuthConsumer`.
- **V2 organisation extensions** — OAuth consumers CRUD, `namespaces()`.
  New DTO: `Namespace_`.
- **V4 drains** — `DrainsResource` with full CRUD plus `enable()` /
  `disable()` / `restart()`. New `Drain` DTO and `DrainType` enum
  (Datadog / ElasticSearch / NewRelic / OVH-TCP / Raw-HTTP /
  Syslog-TCP / Syslog-UDP).
- **V4 notifications** — `NotificationsResource` (email notifications,
  list/create/delete with event-type and service filters). New
  `EmailNotification` DTO.
- **V4 webhooks** — `WebhooksResource` (list/create/delete, format
  raw/slack/gitter/flowdock). New `Webhook` DTO and `WebhookFormat`
  enum.
- **V4 network groups** — `NetworkGroupsResource` (list/get/create/
  delete + member management + WireGuard config download for external
  peers). New `NetworkGroup`, `NetworkGroupMember` DTOs. Operator
  resources gained `linkNetworkGroup()` / `unlinkNetworkGroup()`
  helpers (Keycloak / Otoroshi).
- **V4 orchestration** — `OrchestrationResource` exposing the
  v4 instance and deployment endpoints at
  `/v4/orchestration/organisations/{owner}/applications/{appId}/{instances|deployments}`.
- All new resources exposed on the `Client` facade via property hooks
  (`$client->drains`, `$client->notifications`, `$client->webhooks`,
  `$client->networkGroups`, `$client->orchestration`).

### Fixed

- **V4 logs URL** — `LogsResource` now hits
  `/v4/logs/organisations/{owner}/applications/{appId}/logs` (was
  `/v4/logs/{appId}`). The owner is now a required argument.
- **V4 operators URL** — `OperatorsResource` now routes through
  `/v4/addon-providers/addon-{kind}/addons[/{id}]` (was the made-up
  `/v4/operators/{kind}/{owner}/...`). Owner-scoped arguments are
  dropped — the API resolves ownership from credentials.
- **Pulsar storage policies** — `PulsarPoliciesResource` now hits
  `/storage-policies` (not `/policy`) and uses **PATCH** (not PUT) for
  partial updates. The `list()` method is gone; use `get()` to read
  the current effective policy. `delete()` was renamed `reset()`.

### Changed

- Tests now use `Symfony\Component\HttpClient\MockHttpClient` wrapped
  in `Symfony\Component\HttpClient\Psr18Client` instead of an in-tree
  PSR-18 fake. The custom `RecordingClient` and the QueueClient inside
  `HttpClientTest` have been removed.
- **SSE log streaming** now delegates to Symfony's
  `EventSourceHttpClient` + `ServerSentEvent` instead of the in-tree
  WHATWG SSE parser. The `SseStream` / `SseEvent` classes are removed;
  `LogsResource::stream()` still returns an iterable `LogStream<LogEntry>`,
  but framing, reconnection, and `Last-Event-ID` tracking are now
  handled by Symfony. New `SseStreamHandle` bundles the Symfony
  HttpClient + response for the iterator.
- **HTTP transport** — `symfony/http-client` is now a hard runtime
  dependency (it was a dev dep before). `php-http/discovery` is dropped.
  `ClientBuilder::withHttpClient()` now expects a
  `Symfony\Contracts\HttpClient\HttpClientInterface`; the SDK wires it
  internally through `Psr18Client` for regular calls and
  `EventSourceHttpClient` for SSE.

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

[Unreleased]: https://github.com/welcoMattic/clevercloud-php-sdk/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/welcoMattic/clevercloud-php-sdk/releases/tag/v0.1.0
