# Log drains (`/v4/drains/...`)

Source: [`src/Resource/V4/DrainsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/DrainsResource.php)

Drains forward an application's (or add-on's) log stream to an external sink
(Datadog, ElasticSearch, NewRelic, syslog, raw HTTP, …).

## Access

```php
$client->drains
```

`$resourceId` is an application id (`app_...`) or an add-on **real** id
(`postgresql_...`, `cellar_...`, ...): anything that produces logs.

Paths are scoped to `/organisations/{id}`. A null `?string $organisationId`
targets your personal space, whose `user_...` id is read from `GET /v2/self`
first (V4 has no `/self` variant). Paths below write that prefix as `...`.

## Methods

```php
public function list(string $resourceId, ?string $organisationId = null): list<Drain>
public function get(string $resourceId, string $drainId, ?string $organisationId = null): Drain
public function create(string $resourceId, DrainType $type, string $url, array $recipient = [], ?string $organisationId = null, DrainKind $kind = DrainKind::Log, bool $skipCheck = false): Drain
public function delete(string $resourceId, string $drainId, ?string $organisationId = null): void
public function enable(string $resourceId, string $drainId, ?string $organisationId = null): void
public function disable(string $resourceId, string $drainId, ?string $organisationId = null): void
public function restart(string $resourceId, string $drainId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/drains/.../resources/{resourceId}/drains` | |
| `get()` | GET | `/v4/drains/.../resources/{resourceId}/drains/{drainId}` | |
| `create()` | POST | `/v4/drains/.../resources/{resourceId}/drains` (query: `?skipCheck=true` when asked) | `{"kind": "LOG", "recipient": {"type": "<DrainType>", "url": "...", ...}}` |
| `delete()` | DELETE | `/v4/drains/.../resources/{resourceId}/drains/{drainId}` | |
| `enable()` | PUT | `/v4/drains/.../resources/{resourceId}/drains/{drainId}/enable` | |
| `disable()` | PUT | `/v4/drains/.../resources/{resourceId}/drains/{drainId}/disable` | |
| `restart()` | PATCH | `/v4/drains/.../resources/{resourceId}/drains/{drainId}/reset-cursor` | |

`create()` merges `$recipient` into the recipient object: pass the fields the
protocol needs (`username` / `password` for raw HTTP and ElasticSearch,
`apiKey` for NewRelic, `sourceToken` for Better Stack, `token` for OVH TCP and
Splunk, `index`, `sourcetype`, `tlsVerification`,
`rfc5424StructuredDataParameters`). Unless `$skipCheck` is true, the API
probes the target first and refuses one it cannot reach. Creating, enabling
and disabling are asynchronous: poll `get()` until `status['status']` is
`ENABLED` or `DISABLED`.

`restart()` moves the shipping cursor to now: the drain skips its backlog and
only ships the logs produced from then on.

```php
use CleverCloud\Sdk\Model\Enum\DrainType;

$drain = $client->drains->create('app_...', DrainType::NewRelic, 'https://log-api.eu.newrelic.com/log/v1', ['apiKey' => '...'], 'orga_...');
```

## Enums

- `DrainType` (`CleverCloud\Sdk\Model\Enum\DrainType`), the recipient
  protocol: BetterStack, Datadog, ElasticSearch, NewRelic, OvhTcp, RawHttp,
  Splunk, SyslogTcp, SyslogUdp.
- `DrainKind` (`CleverCloud\Sdk\Model\Enum\DrainKind`), the log stream:
  `Log` (`LOG`), `AccessLog` (`ACCESSLOG`).

## `Drain` DTO

`id`, `resourceId`, `kind`, and the raw `status`, `recipient`, `execution`
and `backlog` objects. See
[`src/Model/Drain.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Drain.php).
