# Environment variables (`/v2/.../applications/{id}/env`)

Source: [`src/Resource/V2/EnvironmentResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/EnvironmentResource.php)

The Clever Cloud API returns env vars as a list of `{name, value}` pairs;
the SDK collapses that into a `name => value` map on read. On write,
`setMany()` sends the `name => value` object itself and `set()` sends
`{"value": "..."}`, the name travelling in the path.

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
| `set()` | PUT | `/v2/.../applications/{id}/env/{name}` | `{"value": "..."}` |
| `setMany()` | PUT | `/v2/.../applications/{id}/env` | `{"NAME": "value", ...}` (replaces the entire env; `{}` for an empty map) |
| `remove()` | DELETE | `/v2/.../applications/{id}/env/{name}` | — |

> `setMany()` **replaces** the application's environment entirely — any
> variable not in the passed map is removed.

The change is immediate API-side; values only take effect on the next
deployment of the application.
