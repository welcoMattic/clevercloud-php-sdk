# Clever Cloud PHP SDK

A modern PHP SDK for the [Clever Cloud](https://www.clever-cloud.com) REST API
(v2 + v4).

> Status: work in progress.

## Requirements

- PHP **8.5+**
- A PSR-18 HTTP client (auto-discovered via [`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html);
  Symfony HttpClient is the recommended default).

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

echo $me->id, "\n", $me->email, "\n";
```

## Authentication

Clever Cloud uses **OAuth 1.0a** signed with **HMAC-SHA512** for all API
requests. You'll need a consumer key/secret pair and a user access token.
See [the official docs](https://www.clever.cloud/developers/api/howto/) for
how to obtain them.

## License

[MIT](LICENSE)
