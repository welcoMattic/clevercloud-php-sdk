# Getting started

## Requirements

- PHP **8.5** or later
- The SDK pulls in: `symfony/http-client`, `symfony/http-client-contracts`,
  `nyholm/psr7`, `psr/{clock,http-client,http-factory,http-message,log}`,
  `jolicode/automapper`, `symfony/clock`. All declared in `composer.json`.

## Install

```bash
composer require welcomattic/clevercloud-php-sdk
```

## Pick an authentication mode

The SDK supports two:

```php
use CleverCloud\Sdk\Auth\Credentials;

// Recommended — Personal API token (Bearer). Mint one from
// https://console.clever-cloud.com/.
$creds = Credentials::apiToken('cc_secret_...');

// Legacy — OAuth 1.0a, useful if you already have a consumer pair plus a user
// token from the 3-legged flow.
$creds = Credentials::oauth1(
    consumerKey:    getenv('CC_CONSUMER_KEY'),
    consumerSecret: getenv('CC_CONSUMER_SECRET'),
    token:          getenv('CC_TOKEN'),
    tokenSecret:    getenv('CC_TOKEN_SECRET'),
);
```

[Full authentication reference →](authentication.md)

## Build a client

```php
use CleverCloud\Sdk\ClientBuilder;

$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->build();
```

`ClientBuilder::build()` raises `ConfigurationException` if no credentials have
been set (verified in
[`src/ClientBuilder.php` line 131](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/ClientBuilder.php)).

## Make a call

Every resource is exposed as a property on the `Client` facade and lazily
instantiated on first read (PHP 8.4+ property hook, see
[`src/Client.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Client.php)).

```php
$me   = $client->self->get();                  // -> CleverCloud\Sdk\Model\User
$orgs = $client->organisations->list();        // -> list<Organisation>
$apps = $client->applications->list();         // your own apps
$apps = $client->applications->list('orga_xxx'); // an organisation's apps

foreach ($client->logs->stream('app_xxx') as $entry) {
    printf("[%s] %s\n", $entry->severity ?? 'INFO', $entry->message);
}
```

Every resource page in [the reference](index.md#resource-reference) lists
the full method surface with verified signatures.

## What happens under the hood on each request

1. The targeted resource builds a URI via `UriBuilder` against the right
   API version (V2 / V4 / Bridge) — see
   [`src/Http/UriBuilder.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Http/UriBuilder.php).
2. Your `Credentials` object rewrites the URI if needed (Bearer rewrites
   every V2/V4 call to `api-bridge.clever-cloud.com`).
3. Lifecycle hooks registered via `ClientBuilder::onRequest()` run.
4. The request is signed (OAuth1 HMAC-SHA512) or stamped with
   `Authorization: Bearer ...`.
5. The PSR-18 client (Symfony's `Psr18Client` by default) sends it.
6. On 4xx/5xx: typed `ApiException` subclass is thrown. On 429 with retries
   remaining: sleep per `Retry-After` then retry. On 5xx with retries
   remaining: exponential backoff per `RetryPolicy` then retry.
7. Lifecycle hooks registered via `ClientBuilder::onResponse()` run.
8. The JSON body is decoded and AutoMapper hydrates a typed DTO.

[Configuration knobs and hooks →](configuration.md)
[Error model →](errors.md)
