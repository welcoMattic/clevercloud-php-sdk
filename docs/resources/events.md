# Platform events (`/v2/events`)

Source: [`src/Resource/V2/EventsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/EventsResource.php)

A Server-Sent Events stream of the platform events (deployments, application
and add-on lifecycle) of the current user's personal space and of every
organisation they belong to.

## Access

```php
$client->events
```

## Methods

```php
public function stream(array $types = []): EventStream
```

| Method | HTTP | Path | Returns |
| --- | --- | --- | --- |
| `stream()` | GET (SSE) | `/v2/events` | Iterable `EventStream<StreamEvent>` |

`$types` restricts the stream to some event types and is sent as the
comma-separated `types` query parameter. Every type is streamed when it is
empty.

```php
foreach ($client->events->stream(['DEPLOYMENT_SUCCESS', 'DEPLOYMENT_FAIL']) as $event) {
    printf("%s %s\n", $event->type, json_encode($event->data));
}
```

The stream never ends on its own (the API sends a `keepalive` comment every 30
seconds, which the SDK skips): break out of the loop when you are done. The
API answers 503 when the event feed is unavailable, raised as a
`ServerException`.

## `StreamEvent`

The OpenAPI spec publishes no schema for the event payloads, so each event is
a `CleverCloud\Sdk\Model\StreamEvent` keeping the decoded JSON as is:

```php
public string $type; // the SSE event name, `message` when the server omits it
public array $data; // the JSON-decoded payload
public ?string $id; // the SSE id
```

## Not supported: `/v2/events/event-socket`

The WebSocket variant of this feed (`GET /v2/events/event-socket`, with its
in-band OAuth handshake) is not supported by the SDK. Use `stream()`, which
carries the same events over plain HTTP.
