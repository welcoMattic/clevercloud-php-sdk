# Logs (`/v4/logs/...`)

Source: [`src/Resource/V4/LogsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/LogsResource.php)

Real-time and historical application logs. The live streaming workflow has
its own guide: [Live log streaming](../logs-streaming.md). This page is
the API reference.

## Access

```php
$client->logs
```

## Methods

```php
public function stream(string $applicationId, ?string $organisationId = null, array $filters = []): LogStream
public function query(string $applicationId, ?string $organisationId = null, array $filters = [], int $maxDurationSeconds = 10): list<LogEntry>
```

| Method | HTTP | Path | Returns |
| --- | --- | --- | --- |
| `stream()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/applications/{appId}/logs` | Iterable `LogStream<LogEntry>` |
| `query()` | GET (SSE) | `/v4/logs/organisations/{ownerId}/applications/{appId}/logs` | One-shot `list<LogEntry>` |

`stream()` opens a Server-Sent Events connection via Symfony's
`EventSourceHttpClient`. The returned `LogStream` implements
`IteratorAggregate<int, LogEntry>` - `foreach` over it as logs arrive.
`LogStream` accepts an optional `$maxDurationSeconds` parameter to bound iteration.

`query()` consumes the SSE stream and stops early, returning a one-shot list.
It requires a `since` filter to be historical; without one it degenerates into a
live tail. The `$maxDurationSeconds` parameter is mandatory in practice because
the endpoint never closes an idle stream - it emits HEARTBEAT events forever.

## Filter shape

Both methods accept a `$filters` array:

```php
public function stream(
    string $applicationId,
    ?string $organisationId = null,
    array $filters = [], // {since?, until?, filter?, deploymentId?}
): LogStream;

public function query(
    string $applicationId,
    ?string $organisationId = null,
    array $filters = [], // {since?, until?, filter?, deploymentId?, limit?}
): array;
```

Each key passes through to the API as a query parameter.

## `LogEntry` DTO

See [Live log streaming](../logs-streaming.md#logentry-shape) for the full
field list. Key fields: `message`, `severity`, `date`, `instanceId`,
`zone`, `deploymentId`.
