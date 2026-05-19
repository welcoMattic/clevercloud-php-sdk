# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
