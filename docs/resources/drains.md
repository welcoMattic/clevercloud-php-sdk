# Log drains (`/v4/drains/...`)

Source: [`src/Resource/V4/DrainsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/DrainsResource.php)

Drains forward an application's (or add-on's) log stream to an external sink
(Datadog, ElasticSearch, NewRelic, syslog, raw HTTP, …).

## Access

```php
$client->drains
```

`$resourceId` is an application id (`app_…`) or an add-on **real** id
(`postgresql_…`, `cellar_…`, …) — anything that produces logs.

## Methods

```php
public function list(string $resourceId, ?string $organisationId = null): list<Drain>
public function get(string $resourceId, string $drainId, ?string $organisationId = null): Drain
public function create(string $resourceId, DrainType $kind, string $url, array $credentials = [], ?string $organisationId = null): Drain
public function delete(string $resourceId, string $drainId, ?string $organisationId = null): void
public function enable(string $resourceId, string $drainId, ?string $organisationId = null): void
public function disable(string $resourceId, string $drainId, ?string $organisationId = null): void
public function restart(string $resourceId, string $drainId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/drains/.../resources/{resourceId}/drains` | — |
| `get()` | GET | `/v4/drains/.../resources/{resourceId}/drains/{drainId}` | — |
| `create()` | POST | `/v4/drains/.../resources/{resourceId}/drains` | `{"kind": "<DrainType>", "url": "...", "credentials": {...}?}` |
| `delete()` | DELETE | `/v4/drains/.../resources/{resourceId}/drains/{drainId}` | — |
| `enable()` | PUT | `/v4/drains/.../resources/{resourceId}/drains/{drainId}/state` | `{"enabled": true}` |
| `disable()` | PUT | `/v4/drains/.../resources/{resourceId}/drains/{drainId}/state` | `{"enabled": false}` |
| `restart()` | PATCH | `/v4/drains/.../resources/{resourceId}/drains/{drainId}` | `{"action": "restart"}` |

`restart()` resumes log forwarding from the latest position — useful when a
drain has fallen behind and you want to skip the backlog.

## `DrainType` enum

Available cases (`CleverCloud\Sdk\Model\Enum\DrainType`): Datadog,
ElasticSearch, NewRelic, OVH-TCP, Raw-HTTP, Syslog-TCP, Syslog-UDP. Pass
`DrainType::Datadog` etc. to `create()`.
