# Clever Cloud PHP SDK

A modern PHP SDK for the [Clever Cloud](https://www.clever-cloud.com) REST API
(v2 + v4).

> Status: pre-1.0. The public API surface is stable; URL paths for v4 endpoints
> are validated incrementally against the live API.

## Requirements

- PHP **8.5+** (uses property hooks, asymmetric visibility, readonly classes,
  typed enums)
- A PSR-18 HTTP client (auto-discovered via [`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html);
  Symfony HttpClient is the recommended default)

## Installation

```bash
composer require clevercloud/sdk
```

## Quick start

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

$client = (new ClientBuilder())
    ->withCredentials(new Credentials(
        consumerKey:    getenv('CC_CONSUMER_KEY'),
        consumerSecret: getenv('CC_CONSUMER_SECRET'),
        token:          getenv('CC_TOKEN'),
        tokenSecret:    getenv('CC_TOKEN_SECRET'),
    ))
    ->build();

$me = $client->self->get();
echo $me->email, "\n";
```

## What you can do

```php
// Identity
$me   = $client->self->get();                       // -> User
$orgs = $client->organisations->list();             // -> list<Organisation>

// Applications
$apps = $client->applications->list();              // own apps
$apps = $client->applications->list('orga_xxx');    // an org's apps
$app  = $client->applications->get('app_xxx');
$client->applications->restart('app_xxx');

// Environment variables (returned as a flat name => value map)
$env = $client->environment->list('app_xxx');
$client->environment->set('app_xxx', 'API_KEY', 'secret');

// Domains (vhosts)
$client->domains->add('app_xxx', 'demo.example.com');

// Add-ons
$pg = $client->addons->create([
    'name'       => 'my-pg',
    'region'     => 'par',
    'providerId' => 'postgresql-addon',
    'plan'       => 'plan_dev',
]);

// Billing (v4)
foreach ($client->billing->listInvoices() as $invoice) {
    echo $invoice->invoiceNumber, ' — ', $invoice->status, "\n";
}

// Real-time logs (v4 SSE)
foreach ($client->logs->stream('app_xxx') as $entry) {
    printf("[%s] %s\n", $entry->severity ?? 'INFO', $entry->message);
}

// Catalog (v4)
$client->products->zones();
$client->products->instances();
```

## Authentication

Clever Cloud uses **OAuth 1.0a** signed with **HMAC-SHA512** for all API
requests. You need a consumer key/secret pair plus a user access token/secret.

The bundled `OAuthFlow` helper drives the three-legged exchange:

```php
use CleverCloud\Sdk\Auth\OAuthFlow;
use CleverCloud\Sdk\Auth\OAuth1Signer;

$flow = new OAuthFlow(new OAuth1Signer(), $psr18, $requestFactory);

$req  = $flow->requestToken($consumerKey, $consumerSecret, 'https://app.example/callback');
$url  = $flow->authorizationUrl($req['token']);     // redirect the user here
// ... user authorises, you get an oauth_verifier back at the callback ...
$tok  = $flow->accessToken($consumerKey, $consumerSecret, $req['token'], $req['tokenSecret'], $verifier);

// $tok['token'] / $tok['tokenSecret'] now go into Credentials.
```

## Configuration knobs

```php
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Http\RetryPolicy;

$client = (new ClientBuilder())
    ->withCredentials($credentials)
    ->withConfiguration(new Configuration(userAgent: 'my-app/1.0'))
    ->withRetryPolicy(new RetryPolicy(maxAttempts: 5, baseDelayMs: 250))
    ->withLogger($psr3Logger)         // optional PSR-3 logger
    ->withHttpClient($psr18Client)    // optional — discovery picks one otherwise
    ->build();
```

## Error handling

Everything the SDK throws implements `CleverCloud\Sdk\Exception\CleverCloudException`.
HTTP errors land in typed subclasses of `ApiException`:

| Status               | Exception              |
| -------------------- | ---------------------- |
| 401 / 403            | `AuthException`        |
| 404                  | `NotFoundException`    |
| 400 / 422 + `errors` | `ValidationException`  |
| 429                  | `RateLimitException`   |
| 5xx                  | `ServerException`      |
| other 4xx            | `ApiException`         |
| PSR-18 transport     | `TransportException`   |
| bad SDK config       | `ConfigurationException` |

All `ApiException`s carry `$statusCode`, `$errorCode`, `$requestId`, and the
decoded `$body`. `ValidationException` adds a `field => list<message>` map,
`RateLimitException` adds `$retryAfterSeconds`.

## Testing against the SDK

`Symfony\Component\HttpClient\MockHttpClient` wrapped in a `Psr18Client` plugs
straight into `ClientBuilder::withHttpClient()` — see
`tests/Unit/ClientBuilderTest.php` for a working pattern.

## License

[MIT](LICENSE)
