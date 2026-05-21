# Error handling

Every error the SDK raises implements the marker interface
`CleverCloud\Sdk\Exception\CleverCloudException` (extends `\Throwable`). Catch
that to handle any SDK failure, then narrow as needed.

```
\Throwable
└── \RuntimeException
    └── CleverCloudException (interface)
        ├── ApiException                — HTTP 4xx / 5xx returned by Clever Cloud
        │   ├── AuthException           — 401 / 403
        │   ├── NotFoundException       — 404
        │   ├── ValidationException     — 400 / 422 with field-level errors
        │   ├── RateLimitException      — 429 (after retries exhausted)
        │   └── ServerException         — 5xx (after retries exhausted)
        ├── TransportException          — PSR-18 network / TLS / DNS failure
        ├── ConfigurationException      — invalid builder / RetryPolicy input
        └── JsonException               — invalid JSON body
```

## `ApiException`

Base for every HTTP error the platform returns. It's **concrete** — raised
verbatim for any 4xx that doesn't match a more specific subclass (e.g.
418, 409, etc.).

```php
use CleverCloud\Sdk\Exception\ApiException;

try {
    $client->applications->restart('app_xxx');
} catch (ApiException $e) {
    $e->statusCode;  // int — HTTP status
    $e->errorCode;   // ?string — `code` / `error_code` / `type` field from the body
    $e->requestId;   // ?string — `X-Request-Id` / `Sozu-Id` / `X-Sozu-Id`
    $e->body;        // array<string, mixed> — decoded JSON body (empty on no/invalid body)
    $e->getMessage();
    $e->getPrevious();
}
```

All five properties are public readonly. Constructor signature verified in
[`src/Exception/ApiException.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Exception/ApiException.php).

## `AuthException` — 401 / 403

Same shape as `ApiException`. Raised when Clever Cloud rejects your
credentials or your token lacks the required scope.

## `NotFoundException` — 404

Same shape as `ApiException`. Raised when the targeted resource doesn't
exist (or you're not allowed to see it).

## `ValidationException` — 400 / 422

Adds a `$errors` field on top of the base properties:

```php
use CleverCloud\Sdk\Exception\ValidationException;

try {
    $client->applications->create(['name' => '']);
} catch (ValidationException $e) {
    $e->errors;  // array<string, list<string>> — field name => list of messages
    foreach ($e->errors as $field => $messages) {
        printf("%s: %s\n", $field, implode(', ', $messages));
    }
}
```

The map is filled from the response body's `errors` or `violations` field
(in that order). String values get wrapped in a single-element list so the
shape is always `field => list<string>`.

See [`src/Exception/ValidationException.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Exception/ValidationException.php).

## `RateLimitException` — 429

Adds `$retryAfterSeconds`:

```php
use CleverCloud\Sdk\Exception\RateLimitException;

try {
    $client->applications->list();
} catch (RateLimitException $e) {
    $e->retryAfterSeconds;  // ?int — server-provided Retry-After in seconds
}
```

This exception is only raised when retries have been **exhausted**. While
attempts remain, the SDK transparently sleeps for `Retry-After` (or the
`RetryPolicy` fallback) and retries.

See [`src/Exception/RateLimitException.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Exception/RateLimitException.php).

## `ServerException` — 5xx

Same shape as `ApiException`. Raised when the platform returns a 5xx **and**
retries have been exhausted (`RetryPolicy::$maxAttempts`).

## `TransportException`

Wraps a `Psr\Http\Client\ClientExceptionInterface` (DNS failure, TLS handshake
error, connection reset, etc.). Raised after retries on transport errors
are exhausted.

```php
use CleverCloud\Sdk\Exception\TransportException;

try {
    $client->self->get();
} catch (TransportException $e) {
    $e->getMessage();        // "Transport error after N attempt(s): <psr18 message>"
    $e->getPrevious();       // the underlying ClientExceptionInterface
}
```

## `ConfigurationException`

Raised eagerly when:

- `ClientBuilder::build()` is called without credentials.
- `RetryPolicy::__construct()` receives invalid values (negative delays,
  `multiplier < 1.0`, `maxAttempts < 1`).

## `JsonException`

Raised by `JsonCodec` on malformed JSON in API responses, or by
`AbstractResource::mapTo()` when AutoMapper returns `null` for a payload.

## How status codes map to exception classes

Verified against `HttpClient::mapError()` in
[`src/Http/HttpClient.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Http/HttpClient.php):

| HTTP status | Exception                |
| ----------- | ------------------------ |
| 401, 403    | `AuthException`          |
| 404         | `NotFoundException`      |
| 400, 422    | `ValidationException`    |
| 429         | `RateLimitException` (after retries exhausted) |
| ≥ 500       | `ServerException` (after retries exhausted)    |
| other 4xx   | `ApiException`           |

## Body / message / request ID extraction

Verified against `HttpClient::extractMessage()`, `extractErrorCode()`,
`extractRequestId()`:

- **Message**: first non-empty of `body.message`, `body.error`,
  `body.error_description`, `body.detail`, then `Response::getReasonPhrase()`,
  finally `"HTTP {code}"`.
- **Error code**: first non-empty of `body.code`, `body.error_code`,
  `body.type`.
- **Request ID**: first non-empty of headers `X-Request-Id`, `Sozu-Id`,
  `X-Sozu-Id`.
- **Body**: decoded as `array<string, mixed>` when the JSON is an object;
  `{_raw: <original>}` when it's a list or when JSON decoding fails.

## Catching everything

```php
use CleverCloud\Sdk\Exception\CleverCloudException;

try {
    // anything on $client
} catch (CleverCloudException $e) {
    // SDK-level failure
}
```
