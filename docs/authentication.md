# Authentication

The SDK supports two credential types. Both are built through named constructors
on the abstract `CleverCloud\Sdk\Auth\Credentials` class.

## API token (Bearer) — recommended

Mint a Personal API token from
[the Console](https://console.clever-cloud.com/) (section "Personal API tokens"),
then:

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken(getenv('CC_API_TOKEN')))
    ->build();
```

### What the SDK does

`Credentials::apiToken()` returns an
[`ApiTokenCredentials`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Auth/ApiTokenCredentials.php) which:

1. Attaches `Authorization: Bearer <token>` to every outgoing request
   (`applyTo()` method).
2. **Rewrites the URI to `api-bridge.clever-cloud.com` for V2 and V4 calls**
   (`rewriteUri()` method). The api-bridge gateway is the only host that
   honours Personal API tokens.
3. Leaves `ApiVersion::Bridge` URIs alone — they're already pointed at the
   bridge.

The host swap preserves path, query and headers. Scheme/host/port come from
`Configuration::$bridgeBaseUrl` (defaults to `https://api-bridge.clever-cloud.com`,
see [`src/Configuration.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Configuration.php)).

### Token scopes

The Console lets you pick scopes when creating a token (`application:read`,
`application:write`, `addon:read`, etc.). The SDK does not enforce scopes —
the gateway returns 403 / 404 if a call exceeds them.

## OAuth 1.0a — legacy

Useful when you already have a consumer pair and a long-lived user
token / secret, or when you're driving the 3-legged authorisation flow.

```php
$client = (new ClientBuilder())
    ->withCredentials(Credentials::oauth1(
        consumerKey:    getenv('CC_CONSUMER_KEY'),
        consumerSecret: getenv('CC_CONSUMER_SECRET'),
        token:          getenv('CC_TOKEN'),        // optional
        tokenSecret:    getenv('CC_TOKEN_SECRET'), // optional
    ))
    ->build();
```

`token` and `tokenSecret` are optional only for the two-legged
`/oauth/request_token` step — every authenticated call to the platform
requires both.

### What the SDK does

`Credentials::oauth1()` returns an
[`OAuth1Credentials`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Auth/OAuth1Credentials.php) which:

1. Signs each request with HMAC-SHA512 per RFC 5849 using
   [`OAuth1Signer`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Auth/OAuth1Signer.php).
2. Keeps the URI untouched — calls go to `api.clever-cloud.com` (V2 / V4).

The signer is stateless beyond its injected
`Psr\Clock\ClockInterface` and `Auth\NonceGenerator` — you can pin both for
deterministic test fixtures.

## 3-legged OAuth flow helper

`CleverCloud\Sdk\Auth\OAuthFlow` drives the three round-trips for you. Wire
it up with a PSR-18 client + factory:

```php
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Auth\OAuthFlow;
use CleverCloud\Sdk\Auth\Credentials;

$flow = new OAuthFlow($signer, $psr18Client, $requestFactory);

// Step 1: temporary request token
$req = $flow->requestToken($consumerKey, $consumerSecret, 'https://app.example/callback');
// $req: ['token' => '...', 'tokenSecret' => '...']

// Step 2: send the user here, they authorise on the Console
$authorizeUrl = $flow->authorizationUrl($req['token']);

// Step 3: when they come back with ?oauth_verifier=...
$access = $flow->accessToken(
    $consumerKey, $consumerSecret,
    $req['token'], $req['tokenSecret'],
    $verifier,
);
// $access: ['token' => '...', 'tokenSecret' => '...']

// Step 4: build long-lived credentials
$creds = Credentials::oauth1($consumerKey, $consumerSecret, $access['token'], $access['tokenSecret']);
```

The helper speaks `application/x-www-form-urlencoded` (per RFC 5849) and
bypasses the regular `HttpClient` stack — see
[`src/Auth/OAuthFlow.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Auth/OAuthFlow.php).

It raises:

- `TransportException` on PSR-18 transport errors.
- `ApiException` on any non-2xx response or missing `oauth_token` /
  `oauth_token_secret` in the body.

## Custom signer clock / nonce generator

If you need reproducible signatures (tests, audit logs):

```php
use Symfony\Component\Clock\MockClock;
use CleverCloud\Sdk\Auth\NonceGenerator;

final class FixedNonce implements NonceGenerator
{
    public function __construct(private string $value) {}
    public function generate(): string { return $this->value; }
}

$client = (new ClientBuilder())
    ->withCredentials($creds)
    ->withClock(new MockClock('@1700000000'))
    ->withNonceGenerator(new FixedNonce('test-nonce'))
    ->build();
```

The clock implements `Psr\Clock\ClockInterface`; the SDK reads it via
`OAuth1Signer` to stamp `oauth_timestamp`.

## When to use which

| Scenario | Mode |
| --- | --- |
| CI scripts, server-to-server automation | API token |
| Interactive web app (your users grant access) | OAuth 1.0a 3-legged |
| Mobile / CLI client | Either, API token is simpler |
| Calls to `api-bridge.clever-cloud.com` endpoints (e.g. token management) | **API token only** |
