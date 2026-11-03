# Authentication

The SDK supports three credential types. All are built through named constructors
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

## Service token (biscuit)

Service tokens are [biscuit](https://www.biscuitsec.org/) tokens minted for an
organisation (see the [Service tokens resource](resources/service-tokens.md)).
They act on the organisation's behalf with a role (`ADMIN`, `MANAGER`,
`DEVELOPER`, `ACCOUNTING`), optionally restricted to some applications and
add-ons, and expire (90 days by default, one year at most):

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

$client = (new ClientBuilder())
    ->withCredentials(Credentials::serviceToken(getenv('CC_SERVICE_TOKEN')))
    ->build();

$apps = $client->applications->list('orga_xxx');
```

### What the SDK does

`Credentials::serviceToken()` returns a
[`ServiceTokenCredentials`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Auth/ServiceTokenCredentials.php) which:

1. Attaches `Authorization: Bearer <token>` to every outgoing request.
2. Keeps the URI untouched: calls go to `api.clever-cloud.com` (V2 / V4),
   **not** to the api-bridge gateway.

### Service tokens vs API tokens

Both are sent as `Authorization: Bearer <token>`, but they are different
credentials:

| | API token | Service token |
| --- | --- | --- |
| Factory | `Credentials::apiToken()` | `Credentials::serviceToken()` |
| Acts as | a user | an organisation, with a role |
| Host | `api-bridge.clever-cloud.com` (the SDK reroutes V2 / V4 calls) | `api.clever-cloud.com` (no reroute) |
| Minted via | the Console or [`$client->apiTokens`](resources/api-tokens.md) | [`$client->serviceTokens`](resources/service-tokens.md) |
| Scope | scopes picked at creation | role + optional application / add-on list, carried by the token |
| Refresh | - | none, mint a new one before expiry |

Both management resources require an OAuth 1.0a client: a Bearer token
cannot mint Bearer tokens.

### Where service tokens are accepted

The API only accepts a service token on the routes whose OpenAPI security
declares the `biscuit` scheme. These are all organisation-scoped, and none
lives under `/v2/self`. In the current spec:

- `/v2/organisations/{id}/applications/**`: applications and their add-on
  links, dependencies, deployments, environment variables, instances, tags,
  TCP / UDP redirections, vhosts, GitHub webhook secret (45 operations).
- `PUT /v2/organisations/{id}/avatar`.
- `/v4/networkgroups/organisations/{ownerId}/networkgroups/**`: network
  groups, members, peers, external peers, WireGuard configuration and the
  event streams (19 operations).
- `/v4/kubernetes/organisations/{ownerId}/clusters/{kubernetesId}/node-groups/**`
  (6 operations).
- `GET /v4/addon-providers/addon-pulsar/clusters/{clusterId}`.

Every other route (`/v2/self`, organisation members and add-ons, service
token management, billing, ...) only declares OAuth 1.0a: the API refuses a
service token there. Pass the organisation ID explicitly (e.g. `$client->applications->list('orga_xxx')`,
not `list()` which targets `/v2/self`). The SDK does not check upfront: a
request outside the token's scope is refused by the API.

## OAuth 1.0a — legacy

Useful when you already have a registered consumer pair, or when you're
driving the 3-legged authorisation flow on behalf of your users.

The consumer key + secret identify your application — they're what you
configure (e.g. via env vars). The user token + token secret are
**outputs** of the 3-legged flow, not inputs you provide up front:

```php
// Once you've obtained the user token from the 3-legged flow below:
$client = (new ClientBuilder())
    ->withCredentials(Credentials::oauth1(
        consumerKey: getenv('CC_CONSUMER_KEY'),
        consumerSecret: getenv('CC_CONSUMER_SECRET'),
        token: $userToken, // returned by OAuthFlow::accessToken()
        tokenSecret: $userTokenSecret, // returned by OAuthFlow::accessToken()
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

### Callback URL constraints

The `$callbackUrl` passed to `requestToken()` is mandatory and validated
server-side against the **scheme and host** of your OAuth consumer's *Base URL*
(the port and the path are not checked). A mismatch answers
`HTTP 400 {"id":13502,"message":"OAuth callback is invalid"}`, so
`https://app.example/cb` fails against a consumer registered as
`http://app.example`. Check the registered value with
`clever oauth-consumers get <consumer-key>`.

The out-of-band value `oob` is **not** supported: Clever Cloud answers
`HTTP 500`. A CLI has to send a real URL and read `oauth_verifier` back from the
browser's address bar; see
[`examples/stream-logs-oauth.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/examples/stream-logs-oauth.php).

The helper speaks `application/x-www-form-urlencoded` (per RFC 5849) and
bypasses the regular `HttpClient` stack, see
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
| Organisation automation on applications, network groups, Kubernetes node groups | Service token |
| Interactive web app (your users grant access) | OAuth 1.0a 3-legged |
| Mobile / CLI client | Either, API token is simpler |
| Calls to `api-bridge.clever-cloud.com` endpoints (e.g. token management) | **API token only** |
