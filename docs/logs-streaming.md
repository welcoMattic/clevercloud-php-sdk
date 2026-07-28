# Live log streaming

Clever Cloud streams application logs over Server-Sent Events (SSE). The SDK wraps
Symfony's `EventSourceHttpClient` so you iterate typed `LogEntry` objects -
framing, reconnection, and `Last-Event-ID` resume are Symfony's job.

## Read live logs

```php
foreach ($client->logs->stream('app_xxx', 'orga_xxx') as $entry) {
    printf("[%s] %s\n", $entry->severity ?? 'INFO', $entry->message);
}
```

Signature (verified against
[`src/Resource/V4/LogsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/LogsResource.php)):

```php
public function stream(
    string $applicationId,
    ?string $organisationId = null,
    array $filters = [], // {since?, until?, filter?, deploymentId?}
): LogStream
```

- `$organisationId === null` triggers a `/v2/self` lookup to resolve the personal
  organisation id, costing one extra request. There is no `/self` variant of the
  logs endpoint; the personal organisation is addressed by its own `user_<uuid>` id.
- Filter keys accepted: `since`, `until`, `filter`, `deploymentId`. Everything
  goes into the query string as-is.
- The returned `LogStream` implements `IteratorAggregate<int, LogEntry>` -
  `foreach` is the only public consumer surface. `LogStream` now accepts an optional
  `$maxDurationSeconds` parameter to bound iteration time.
- Both `stream()` and `query()` use the same SSE endpoint. There is **no JSON endpoint**;
  the API only supports `text/event-stream` content type.

## `LogEntry` shape

Verified against [`src/Model/LogEntry.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/LogEntry.php).
The API sends camelCase field names; the model now matches the wire format:

```php
public string  $message;       // required
public ?string $id;
public ?string $severity;
public ?int    $priority;
public ?string $date;
public ?string $instanceId;
public ?string $applicationId;
public ?string $deploymentId;
public ?string $commitId;
public ?string $service;       // e.g. "run-p2993-i2993" (previously misnamed as $stream)
public ?string $region;
public ?string $zone;
public ?string $version;
public array   $raw;           // any extra fields the API may add later
```

Note: `instanceId`, `applicationId` and `deploymentId` were always null before the
fix because the model asked for snake_case keys the API never sends.

## Historical query

When you don't need live tailing - `query()` returns a one-shot list by consuming
the SSE stream and stopping early:

```php
/** @var list<LogEntry> $logs */
$logs = $client->logs->query('app_xxx', 'orga_xxx', [
    'since' => '2026-05-01T00:00:00Z',
    'until' => '2026-05-02T00:00:00Z',
    'filter' => 'level:error',
    'limit' => 100,
], 30); // maxDurationSeconds - required to bound quiet streams
```

Signature:

```php
public function query(
    string $applicationId,
    ?string $organisationId = null,
    array $filters = [], // {since?, until?, filter?, deploymentId?, limit?}
    int $maxDurationSeconds = 10,
): array
```

**Important:** `query()` needs a `since` filter to be historical at all. Without one,
the endpoint only sends entries produced from that moment on, so the call
degenerates into a short live tail. Two bounds are needed: `limit` is sent
upstream and the server closes the connection once that many entries have been
sent, but for quiet applications the endpoint holds the connection open and
emits HEARTBEAT events forever. No combination of `since` / `until` / `limit`
makes it hang up (verified against the live API), so `$maxDurationSeconds` is
what actually guarantees this method returns.

## Endpoint and authentication

Both methods hit:

```
GET /v4/logs/organisations/{ownerId}/applications/{applicationId}/logs
```

The endpoint only supports Server-Sent Events with content type
`text/event-stream`. There is no JSON endpoint, which is why `query()` consumes
the stream instead of doing a plain GET. The
`/v2/organisations/{org}/applications/{app}/logs` path that this SDK targeted
before does not exist and always answered 404.

Both credential types work. An earlier version of this page claimed logs
required OAuth 1.0a and that API tokens always got a 404. That was a
misdiagnosis: the 404 came from the wrong path above, not from the
authentication mode. Streaming and querying logs with a Bearer token through
`api-bridge.clever-cloud.com` is verified working.

The host depends on your credentials, which is the same routing rule as every
other call, see [Authentication](authentication.md):

- **API token (Bearer)** goes to `api-bridge.clever-cloud.com`
- **OAuth 1.0a** goes to `api.clever-cloud.com`

## How `LogStream` decodes frames

Verified against
[`src/Streaming/LogStream.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Streaming/LogStream.php):

1. Symfony's `EventSourceHttpClient` yields chunks. The SDK only handles
   `ServerSentEvent` chunks; first-chunk markers and control frames are
   skipped.
2. Each event's `data` field is JSON-decoded. Empty or invalid payloads
   are silently dropped.
3. AutoMapper maps the decoded array onto a `LogEntry`.
4. Non-2xx upstream responses surface as typed SDK exceptions
   (`AuthException` / `NotFoundException` / `ServerException` /
   `ApiException`) with the actual status code on the first chunk.
5. PSR-18 / Symfony transport failures (DNS, TLS, connection reset)
   raise `TransportException`.

## Resuming after a disconnect

`EventSourceHttpClient` keeps track of the last received event ID and sends
it back on reconnect via the `Last-Event-ID` header. You don't have to do
anything - the iteration just resumes.

## Mocking the stream in tests

```php
$frame1 = json_encode(['message' => 'hello', 'instanceId' => 'i_1']);
$frame2 = json_encode(['message' => 'world', 'instanceId' => 'i_2']);

$response = new MockResponse(
    ['data: '.$frame1."\n\n", 'data: '.$frame2."\n\n"],
    ['response_headers' => ['content-type' => 'text/event-stream']],
);

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken('test'))
    ->withHttpClient(new MockHttpClient([$response]))
    ->build();

$entries = iterator_to_array($client->logs->stream('app_42', 'orga_1'), false);
// 2 LogEntry instances
```

More detail in [Testing](testing.md).

## Proxying SSE to a browser (e.g. dashboard)

The demo dashboard at [`demo/`](https://github.com/welcoMattic/clevercloud-php-sdk/tree/main/demo) wraps `LogStream` in a Symfony
`StreamedResponse` and re-emits each entry as an SSE frame to the browser's
`EventSource`. See
[`demo/src/Controller/LogsController.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/demo/src/Controller/LogsController.php)
for the working pattern (heartbeats, session lock release, typed error
events).
