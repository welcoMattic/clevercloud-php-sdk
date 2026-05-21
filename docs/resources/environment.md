# Environment variables (`/v2/.../applications/{id}/env`)

Source: [`src/Resource/V2/EnvironmentResource.php`](../../src/Resource/V2/EnvironmentResource.php)

The Clever Cloud API exchanges env vars as a list of `{name, value}` pairs.
The SDK collapses that into a `name => value` map on read and expands it
back on write.

## Access

```php
$client->environment
```

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null): array<string, string>
public function get(string $applicationId, string $name, ?string $organisationId = null): ?string
public function set(string $applicationId, string $name, string $value, ?string $organisationId = null): void
public function setMany(string $applicationId, array $variables, ?string $organisationId = null): void
public function remove(string $applicationId, string $name, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v2/.../applications/{id}/env` | — (returns `name => value` map) |
| `get()` | — | (resolved by `list()` then map lookup; no dedicated endpoint) | — |
| `set()` | PUT | `/v2/.../applications/{id}/env/{name}` | `{"name": "...", "value": "..."}` |
| `setMany()` | PUT | `/v2/.../applications/{id}/env` | `[{"name": "...", "value": "..."}, ...]` (replaces the entire env) |
| `remove()` | DELETE | `/v2/.../applications/{id}/env/{name}` | — |

> `setMany()` **replaces** the application's environment entirely — any
> variable not in the passed map is removed.

The change is immediate API-side; values only take effect on the next
deployment of the application.
