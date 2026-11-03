# Logs (`/v4/logs/...`, `/v4/accesslogs/...`)

Source: [`src/Resource/V4/LogsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/LogsResource.php)

Real-time and historical runtime logs and HTTP access logs. The live
streaming workflow has its own guide: [Live log streaming](../logs-streaming.md).
This page is the API reference. Bulk exports of logs to an archive live in
[Log exports](log-exports.md).

## Access

```php
$client->logs
```

## Methods

```php
// Runtime logs of an application
public function stream(string $applicationId, ?string $organisationId = null, array $filters = []): LogStream
public function query(string $applicationId, ?string $organisationId = null, array $filters = [], int $maxDurationSeconds = 10): list<LogEntry>

// Runtime logs of an add-on or a Kubernetes cluster
public function streamResource(string $resourceId, ?string $organisationId = null, array $filters = []): LogStream
public function queryResource(string $resourceId, ?string $organisationId = null, array $filters = [], int $maxDurationSeconds = 10): list<LogEntry>

// HTTP access logs
public function streamAccessLogs(string $applicationId, ?string $organisationId = null, array $filters = []): EventStream
public function queryAccessLogs(string $applicationId, ?string $organisationId = null, array $filters = [], int $maxDurationSeconds = 10): list<StreamEvent>
public function streamResourceAccessLogs(string $resourceId, ?string $organisationId = null, array $filters = []): EventStream
public function queryResourceAccessLogs(string $resourceId, ?string $organisationId = null, array $filters = [], int $maxDurationSeconds = 10): list<StreamEvent>
```

| Method | HTTP | Path | Returns |
| --- | --- | --- | --- |
| `stream()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/applications/{appId}/logs` | Iterable `LogStream<LogEntry>` |
| `query()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/applications/{appId}/logs` | One-shot `list<LogEntry>` |
| `streamResource()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/resources/{resourceId}/logs` | Iterable `LogStream<LogEntry>` |
| `queryResource()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/resources/{resourceId}/logs` | One-shot `list<LogEntry>` |
| `streamAccessLogs()` | GET (SSE) | `/v4/accesslogs/organisations/{ownerId}/applications/{appId}/accesslogs` | Iterable `EventStream<StreamEvent>` |
| `queryAccessLogs()` | GET (SSE) | `/v4/accesslogs/organisations/{ownerId}/applications/{appId}/accesslogs` | One-shot `list<StreamEvent>` |
| `streamResourceAccessLogs()` | GET (SSE) | `/v4/accesslogs/organisations/{ownerId}/resources/{resourceId}/accesslogs` | Iterable `EventStream<StreamEvent>` |
| `queryResourceAccessLogs()` | GET (SSE) | `/v4/accesslogs/organisations/{ownerId}/resources/{resourceId}/accesslogs` | One-shot `list<StreamEvent>` |

Every endpoint only speaks Server-Sent Events. `stream*()` methods open a
connection via Symfony's `EventSourceHttpClient` and return an iterable you
`foreach` over as events arrive. `query*()` methods consume the same stream
and stop early, returning a one-shot list; they send `limit=100` unless you
pass your own `limit`.

A `query*()` call needs a `since` filter to be historical; without one it
degenerates into a live tail. The `$maxDurationSeconds` parameter is mandatory
in practice because the endpoint never closes an idle stream: it emits
HEARTBEAT events forever.

`$organisationId === null` resolves the personal organisation id through
`GET /v2/self`, at the cost of one extra request.

### Which id goes where

- `stream()` / `query()`: an application id (`app_…`).
- `streamResource()` / `queryResource()`: an add-on id (`addon_…`), an add-on
  real id (`postgresql_…`, …) or a Kubernetes cluster id (`kubernetes_…`).
  It also accepts an application id, but then only streams add-on services:
  pass `'service' => 'all'` or use `stream()`.
- `streamAccessLogs()` / `queryAccessLogs()`: an application id or an add-on
  real id, used as given (an `addon_…` id is not resolved).
- `streamResourceAccessLogs()` / `queryResourceAccessLogs()`: an application
  id, an add-on id or an add-on real id.

## Filters

Every key passes through to the API as a query parameter. List values are sent
as a repeated parameter (`instanceId=a&instanceId=b`), booleans as `true` /
`false`.

| Key | Type | `stream` / `query` | `streamResource` / `queryResource` | access logs | Meaning |
| --- | --- | --- | --- | --- | --- |
| `since` | `string` | yes | yes | yes | Start of the stream, RFC 3339 |
| `until` | `string` | yes | yes | yes | End of the stream, RFC 3339 |
| `limit` | `int` | yes | yes | yes | Max number of lines; the stream then ends |
| `deploymentId` | `string` | yes | no | no | Only the logs of this deployment |
| `instanceId` | `string` or `list<string>` | yes | yes | no | Only the logs of these instances |
| `filter` | `string` | yes | yes | no | Only lines containing this text (case-insensitive) |
| `service` | `string` or `list<string>` | yes | yes | no | Services matching these regular expressions, `all` for every service |
| `search` | `string` | no | Kubernetes only | no | Lines containing every word, the last one as a prefix |
| `tag` | `string` or `list<string>` | no | Kubernetes only | no | `key:value` or bare `key` tags |
| `severity` | `string` | no | Kubernetes only | no | Minimum severity (`debug`, `info`, `warning`, `err`, `crit`, …) |
| `field` | `string` or `list<string>` | yes | yes | yes | Only these top-level JSON keys in each event |
| `maxRetryDurationInSeconds` | `int` | yes | yes | yes | Max time to establish the stream (default 600, max 3600) |
| `throttleElements` | `int` | yes | yes | yes | Max events per throttle window |
| `throttlePerInMilliseconds` | `int` | yes | yes | yes | Length of the throttle window |
| `color` | `bool` | yes | yes | no | Keep ANSI colours in `message` (default `true`) |

## `LogEntry` DTO

See [Live log streaming](../logs-streaming.md#logentry-shape) for the full
field list. Key fields: `message`, `severity`, `date`, `instanceId`,
`zone`, `deploymentId`.

## Access logs: `StreamEvent`

The OpenAPI spec publishes no schema for the `ACCESS_LOG` payload, so access
logs come back as `CleverCloud\Sdk\Model\StreamEvent`, which keeps the decoded
JSON as is:

```php
public string $type; // the SSE event name, `ACCESS_LOG`
public array $data; // the JSON-decoded payload
public ?string $id; // the SSE id, for `Last-Event-ID` resume
```

```php
foreach ($client->logs->streamAccessLogs('app_xxx', 'orga_xxx') as $event) {
    printf("%s\n", json_encode($event->data));
}
```
