# Configuration

Every knob you can turn on the SDK is wired through `ClientBuilder`. Methods
are fluent (each returns a clone) — the actual `build()` only runs once you
call it explicitly.

## `Configuration`

```php
use CleverCloud\Sdk\Configuration;

new Configuration(
    v2BaseUrl: 'https://api.clever-cloud.com/v2',
    v4BaseUrl: 'https://api.clever-cloud.com/v4',
    bridgeBaseUrl: 'https://api-bridge.clever-cloud.com',
    userAgent: 'clevercloud-sdk-php',
    timeoutSeconds: 30,
);

// All arguments are optional and default to the values shown above.
```

Fields are public readonly. Defaults live as `const` on the class — see
[`src/Configuration.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Configuration.php).

`baseUrlFor(ApiVersion $version): string` returns the right base for the
three known versions (V2, V4, Bridge).

```php
$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withConfiguration(new Configuration(userAgent: 'my-app/1.0'))
    ->build();
```

## `RetryPolicy`

Controls the SDK's automatic retry behaviour on 429 (rate-limit) and 5xx
responses.

```php
use CleverCloud\Sdk\Http\RetryPolicy;

new RetryPolicy(
    maxAttempts: 3,
    baseDelayMs: 200,
    multiplier: 2.0,
    jitterMs: 100,
    maxDelayMs: 5_000,
);

// All arguments shown above are the defaults: initial try plus 2 retries,
// 200ms base delay, exponential 2× backoff, 0..100ms jitter, capped at 5s.

// Or disable retries entirely (single attempt, no delay, no jitter):
RetryPolicy::none();
```

The constructor validates inputs and raises `ConfigurationException` if any
of these hold (see [`src/Http/RetryPolicy.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Http/RetryPolicy.php)):

- `maxAttempts < 1`
- `baseDelayMs < 0` or `jitterMs < 0` or `maxDelayMs < 0`
- `multiplier < 1.0`

`delayFor(int $attempt): int` returns
`min(baseDelayMs × multiplier^(attempt-1), maxDelayMs) + random(0, jitterMs)`.

**Note**: 429 responses pin the delay to the server-provided `Retry-After`
header when present — the policy's `delayFor()` is only used as fallback.

```php
$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withRetryPolicy(new RetryPolicy(maxAttempts: 5, baseDelayMs: 250))
    ->build();
```

## Custom HTTP client

```php
use Symfony\Component\HttpClient\HttpClient;

$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withHttpClient(HttpClient::create([
        'timeout' => 10,
        'headers' => ['X-My-Header' => 'value'],
    ]))
    ->build();
```

`withHttpClient()` takes a `Symfony\Contracts\HttpClient\HttpClientInterface`.
The SDK uses it both for regular HTTP calls (wrapped in `Psr18Client`) and
SSE log streaming (wrapped in `EventSourceHttpClient`).

If you don't supply one, the SDK calls `Symfony\Component\HttpClient\HttpClient::create()`
internally (see [`src/ClientBuilder.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/ClientBuilder.php) `build()`).

## Lifecycle hooks

Two callable hooks fire around every request — typed against
`Psr\Http\Message\RequestInterface` and `ResponseInterface`.

### `onRequest`

Runs after URI / body construction, **before authentication**. The return
value replaces the request for the rest of the pipeline. Multiple hooks
compose in registration order.

```php
use Psr\Http\Message\RequestInterface;

$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->onRequest(fn (RequestInterface $req): RequestInterface =>
        $req->withHeader('X-Trace-Id', $traceId))
    ->build();
```

### `onResponse`

Runs on every response — success and error. Read-only; the return value is
ignored.

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

$client = (new ClientBuilder())
    ->onResponse(function (ResponseInterface $res, RequestInterface $req) use ($metrics): void {
        $metrics->record(
            $req->getMethod().' '.$req->getUri()->getPath(),
            $res->getStatusCode(),
            $res->getHeaderLine('Sozu-Id'),
        );
    })
    ->withCredentials($creds)
    ->build();
```

**Note**: SSE log streams (`openEventStream()`) bypass the regular dispatch
loop and do **not** fire the response hook on log frames — only on the
initial connection's response.

## PSR-3 logger

Attach any `Psr\Log\LoggerInterface`. The SDK logs on the channel
`clevercloud-sdk` with the following levels and structured keys (verified
against [`src/Http/HttpClient.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Http/HttpClient.php)):

| Level | Message | Context keys |
| --- | --- | --- |
| debug | `clevercloud-sdk: response` | `attempt`, `method`, `uri`, `status`, `requestId` |
| warning | `clevercloud-sdk: transport error` | `attempt`, `method`, `uri`, `exception` |
| warning | `clevercloud-sdk: rate-limited, retrying` | `attempt`, `delayMs` |
| warning | `clevercloud-sdk: server error, retrying` | `attempt`, `status`, `delayMs` |
| error | `clevercloud-sdk: terminal error` | `method`, `uri`, `status`, `exception` |

```php
$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withLogger($psr3Logger)
    ->build();
```

## Custom AutoMapper

The SDK builds one with `AutoMapper\AutoMapper::create()` on `build()`. Pass
your own to share it with the rest of your app:

```php
use AutoMapper\AutoMapper;

$mapper = AutoMapper::create();
// ... configure with your own metadata loaders, transformers, etc.

$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withMapper($mapper)
    ->build();
```

## Custom PSR-7 factories

The SDK uses [`nyholm/psr7`](https://github.com/Nyholm/psr7) by default
(`Nyholm\Psr7\Factory\Psr17Factory`). There's no `withPsr17Factory()` builder
method — if you need a different implementation, instantiate `HttpClient`
yourself instead of using `ClientBuilder` (advanced).
