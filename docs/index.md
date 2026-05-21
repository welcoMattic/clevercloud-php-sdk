# Clever Cloud PHP SDK — documentation

## Guides

- [Getting started](getting-started.md) — install, build a client, make your first call
- [Authentication](authentication.md) — API token (Bearer) and OAuth 1.0a 3-legged
- [Configuration](configuration.md) — `Configuration`, `RetryPolicy`, custom HTTP client, hooks, logging
- [Error handling](errors.md) — the typed exception hierarchy and what each subclass tells you
- [Live log streaming](logs-streaming.md) — iterate `LogStream<LogEntry>` over Symfony SSE
- [Testing your code against the SDK](testing.md) — `MockHttpClient` patterns

## Resource reference

Each page lists every method on the corresponding resource, with the exact
signature and the underlying HTTP route.

### V2

- [Self](resources/self.md)
- [Users](resources/users.md)
- [Organisations](resources/organisations.md)
- [Applications](resources/applications.md)
- [Add-ons](resources/addons.md)
- [Deployments](resources/deployments.md)
- [Environment variables](resources/environment.md)
- [Domains](resources/domains.md)
- [TCP redirections](resources/tcp-redirections.md)
- [Products](resources/products.md) — catalog of instance types, zones, countries, add-on providers

### V4

- [Billing](resources/billing.md)
- [Instances](resources/instances.md)
- [Load balancers](resources/load-balancers.md)
- [Zones](resources/zones.md)
- [Logs](resources/logs.md)
- [Operators](resources/operators.md) — Keycloak / Matomo / Metabase / Otoroshi
- [Drains](resources/drains.md)
- [Notifications](resources/notifications.md)
- [Webhooks](resources/webhooks.md)
- [Network groups](resources/network-groups.md)
- [Orchestration](resources/orchestration.md)
- [Pulsar policies](resources/pulsar-policies.md)
- [Backups](resources/backups.md)

### Bridge (`api-bridge.clever-cloud.com`)

- [API tokens](resources/api-tokens.md)

## Enums

All under `CleverCloud\Sdk\Model\Enum\`:

- `Flavor` — `Pico Nano XS S M L XL XXL XXXL` (`pico..3XL`)
- `DeployType` — `Git Ftp Docker`
- `ApplicationState` — full lifecycle + `isStable()` / `isTransient()` helpers
- `MigrationStatus` — `Success InProgress Pending Failed Cancelled` + `isTerminal()`
- `MemberRole` — `Admin Manager Developer Accounting`
- `DeploymentAction`
- `DeploymentState`
- `DrainType`
- `WebhookFormat`

## Catalogues fetched live from Clever Cloud

| Method | Endpoint | Returns |
| --- | --- | --- |
| `$client->products->instances()` | `GET /v2/products/instances` | `list<InstanceType>` |
| `$client->products->zones()` | `GET /v2/products/zones` | `list<Zone>` |
| `$client->products->countries()` | `GET /v2/products/countries` | `list<Country>` |
| `$client->addons->providers()` | `GET /v2/products/addonproviders` | `list<AddonProvider>` |
| `$client->addons->provider($id)` | `GET /v2/products/addonproviders/{id}` | `AddonProvider` |
| `$client->applications->branches($appId)` | `GET /v2/.../applications/{id}/branches` | `list<string>` |
| `$client->tcpRedirections->namespaces($orgId)` | `GET /v2/.../tcp-redirs/namespaces` | `list<string>` |

## See also

- [Demo dashboard](../demo/README.md) — a working Symfony 8 app that exercises every public method
- [CHANGELOG](../CHANGELOG.md)
