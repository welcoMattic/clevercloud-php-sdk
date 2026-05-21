# Live log streaming

Clever Cloud streams application logs over Server-Sent Events. The SDK wraps
Symfony's `EventSourceHttpClient` so you iterate typed `LogEntry` objects —
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

- `$organisationId === null` scopes to `/self` (your own apps).
- Filter keys accepted: `since`, `until`, `filter`, `deploymentId`. Everything
  goes into the query string as-is.
- The returned `LogStream` implements `IteratorAggregate<int, LogEntry>` —
  `foreach` is the only public consumer surface.

## `LogEntry` shape

Verified against [`src/Model/LogEntry.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/LogEntry.php):

```php
public string  $message;
public ?string $instanceId;     // from `instance_id`
public ?string $applicationId;  // from `application_id`
public ?string $stream;
public ?string $severity;
public ?string $zone;
public ?string $deploymentId;   // from `deployment_id`
public ?string $date;
public array   $raw;            // any extra fields the API may add later
```

## Historical query

When you don't need live tailing — `query()` returns a one-shot list:

```php
/** @var list<LogEntry> $logs */
$logs = $client->logs->query('app_xxx', 'orga_xxx', [
    'since' => '2026-05-01T00:00:00Z',
    'until' => '2026-05-02T00:00:00Z',
    'filter' => 'level:error',
    'limit' => 100,
]);
```

Signature:

```php
public function query(
    string $applicationId,
    ?string $organisationId = null,
    array $filters = [], // {since?, until?, filter?, deploymentId?, limit?}
): array
```

## Endpoint and authentication

Both methods hit:

```
GET /v4/logs/organisations/{ownerId}/applications/{applicationId}/logs
```

— or `/logs/self/applications/{applicationId}/logs` when
`$organisationId === null`. Path constructed by `logsPath()` in
`LogsResource`.

The host depends on your credentials:

- **API token (Bearer)** → `api-bridge.clever-cloud.com`
- **OAuth 1.0a** → `api.clever-cloud.com`

This is the same routing rule as every other call — see
[Authentication](authentication.md).

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
anything — the iteration just resumes.

## Mocking the stream in tests

```php
$frame1 = json_encode(['message' => 'hello', 'instance_id' => 'i_1']);
$frame2 = json_encode(['message' => 'world', 'instance_id' => 'i_2']);

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
