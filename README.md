<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/assets/clevercloud-logo-dark.svg">
    <img src="docs/assets/clevercloud-logo.svg" alt="Clever Cloud" width="320">
  </picture>
</p>

# Clever Cloud PHP SDK

A modern PHP SDK for the [Clever Cloud](https://www.clever-cloud.com) REST API
(v2 + v4 + api-bridge).

> Status: **v1.0**. Public API surface is stable; changes that break
> source compatibility will trigger a major bump per
> [semver](https://semver.org).

## Requirements

- PHP **8.5+** (property hooks, asymmetric visibility, readonly classes, typed enums)
- `symfony/http-client` (hard runtime dep — used as PSR-18 transport **and**
  Symfony's `EventSourceHttpClient` for SSE log streaming)
- `nyholm/psr7` (PSR-7/17 implementation; no discovery, embedded as a default)

## Installation

```bash
composer require welcomattic/clevercloud-php-sdk
```

## Documentation

Full reference under [`docs/`](docs/index.md):

- [Getting started](docs/getting-started.md) — install + first call
- [Authentication](docs/authentication.md) — API token (Bearer) + OAuth 1.0a
- [Configuration](docs/configuration.md) — `Configuration`, `RetryPolicy`, hooks, logging
- [Error handling](docs/errors.md) — typed exception hierarchy
- [Live log streaming](docs/logs-streaming.md) — Symfony SSE under the hood
- [Testing](docs/testing.md) — `MockHttpClient` patterns
- [Resource reference](docs/index.md#resource-reference) — one page per family

## Quick start

Two auth modes, pick one:

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

// Recommended — API token (Bearer), minted from the Console.
$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken(getenv('CC_API_TOKEN')))
    ->build();

// Legacy — OAuth 1.0a, useful if you already have a consumer + user token pair.
$client = (new ClientBuilder())
    ->withCredentials(Credentials::oauth1(
        consumerKey:    getenv('CC_CONSUMER_KEY'),
        consumerSecret: getenv('CC_CONSUMER_SECRET'),
        token:          getenv('CC_TOKEN'),
        tokenSecret:    getenv('CC_TOKEN_SECRET'),
    ))
    ->build();

$me = $client->self->get();
echo $me->email, "\n";
```

## Coverage matrix

The SDK exposes Clever Cloud's full v2 + v4 surface plus the new api-bridge
gateway used for API tokens. Below is the actual coverage shipped for v1.0;
gaps are listed in the "Roadmap" section so you know what's deliberately out
of scope at this stage.

### V2 — application platform

| Family          | Methods on `$client->...`                                                                                                                                                                |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `self`          | `get / update / sshKeys / addSshKey / removeSshKey / emailAddresses / addEmailAddress / removeEmailAddress / consumers / getConsumer / createConsumer / updateConsumer / deleteConsumer / startMfa / confirmMfa / disableMfa / regenerateMfaBackupCodes / changePassword` |
| `users`         | `get / update / delete / applications / addons`                                                                                                                                          |
| `organisations` | `list / get / create / update / delete / members / addMember / updateMember / removeMember / consumers / getConsumer / createConsumer / updateConsumer / deleteConsumer / namespaces`    |
| `applications`  | `list / get / create / update / delete / restart / stop / deploy / branches / setBranch / instances / dependencies / addDependency / removeDependency / tags / addTag / removeTag / exposedEnv / setExposedEnv / addons / linkAddon / unlinkAddon` |
| `addons`        | `list / get / create / update / delete / providers / provider / plans / linkedApplications / env / sso / tags / addTag / removeTag / migrate / listMigrations / getMigration / cancelMigration / preorderMigration` |
| `deployments`   | `list / get / cancel / instances`                                                                                                                                                        |
| `environment`   | `list / get / set / setMany / remove`                                                                                                                                                    |
| `domains`       | `list / get / add / remove / favourite / setFavourite / unsetFavourite`                                                                                                                  |
| `tcpRedirections` | `list / namespaces / add / remove`                                                                                                                                                     |

### V4 — platform extensions

| Family            | Methods on `$client->...`                                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `billing`         | `getBalance / listInvoices / getInvoice / downloadInvoicePdf / paymentMethods / addPaymentMethod / removePaymentMethod / consumptions / recurrent` |
| `instances`       | `list / get / flavors / types`                                                                                           |
| `loadBalancers`   | `list / get / dnsInfo`                                                                                                   |
| `products`        | `instances / addonProviders / zones / countries`                                                                         |
| `zones`           | `list / get`                                                                                                             |
| `pulsarPolicies`  | `get / update / reset`                                                                                                   |
| `logs`            | `stream` (SSE → `LogStream<LogEntry>`) / `query` (historical)                                                            |
| `operators`       | `.keycloak / .matomo / .metabase / .otoroshi` (each: `list / get / create / update / delete / reboot / rebuild`)         |
| `drains`          | `list / get / create / update / delete / enable / disable / restart`                                                     |
| `notifications`   | `list / create / delete` (email hooks)                                                                                   |
| `webhooks`        | `list / create / delete`                                                                                                 |
| `networkGroups`   | `list / get / create / delete / addMember / removeMember / externalPeerConfig`                                           |
| `orchestration`   | `instances / deployments`                                                                                                |
| `backups`         | `list / get / restore`                                                                                                   |

### Bridge — `api-bridge.clever-cloud.com`

| Family        | Methods on `$client->...`                |
| ------------- | ---------------------------------------- |
| `apiTokens`   | `list / get / create / update / delete`  |

### Enums

Stable, platform-wide enumerations live under `CleverCloud\Sdk\Model\Enum\`.
They give you autocomplete, parsing (`tryFrom()`), and `cases()` for populating
form selects without hardcoding string literals.

| Enum                       | Cases                                                                                      | Use it for                              |
| -------------------------- | ------------------------------------------------------------------------------------------ | --------------------------------------- |
| `Flavor`                   | `Pico Nano XS S M L XL XXL XXXL` (values `pico..3XL`)                                      | Application instance sizes              |
| `DeployType`               | `Git Ftp Docker`                                                                           | Application source delivery method      |
| `ApplicationState`         | `ShouldBeUp WantsToBeUp ShouldBeDown WantsToBeDown Restart RestartRequested RestartFailed Deploying DeploymentPending` (+ `isStable()` / `isTransient()`) | Application lifecycle |
| `MigrationStatus`          | `Success InProgress Pending Failed Cancelled` (+ `isTerminal()`)                          | Add-on plan migration                   |
| `MemberRole`               | `Admin Manager Developer Accounting`                                                       | Organisation membership                 |
| `DeploymentAction`         | (see source)                                                                               | Deployment lifecycle                    |
| `DeploymentState`          | (see source)                                                                               | Deployment outcome                      |
| `DrainType`                | Datadog / ElasticSearch / NewRelic / OVH-TCP / Raw-HTTP / Syslog-TCP / Syslog-UDP          | Log drain configuration                 |
| `WebhookFormat`            | Raw / Slack / Gitter / Flowdock                                                            | Webhook payload format                  |

```php
use CleverCloud\Sdk\Model\Enum\Flavor;
use CleverCloud\Sdk\Model\Enum\ApplicationState;

// Populate a UI dropdown:
foreach (Flavor::cases() as $flavor) {
    echo $flavor->value;     // 'pico', 'nano', ...
}

// Branch on application state:
$app = $client->applications->get($id);
$state = ApplicationState::tryFrom($app->state);
if ($state?->isTransient()) {
    // currently deploying or restarting
}

// Build a create-app payload safely:
$client->applications->create([
    'name' => 'my-app',
    'instanceType' => 'node',
    'instanceVariant' => '20',
    'zone' => 'par',
    'minFlavor' => Flavor::Nano->value,
    'maxFlavor' => Flavor::Nano->value,
    'minInstances' => 1,
    'maxInstances' => 1,
]);
```

**Dynamic catalogues** (changes faster than SDK releases) are exposed via API
calls rather than PHP enums — they pick up new entries the moment Clever Cloud
ships them:

```php
$client->products->instances();    // -> list<InstanceType>  (php, node, docker, …)
$client->products->zones();        // -> list<Zone>          (par, mtl, scw, …)
$client->products->countries();    // -> list<Country>
$client->addons->providers();      // -> list<AddonProvider> (postgresql-addon, redis-addon, …)
$client->addons->provider($id);    // -> AddonProvider       (with its plans)
```

### Roadmap (not in v1.0)

- AI, Materia KV / TS, Cellar, Cumulocity, DNS, IPAM, Kubernetes, Function,
  Container Registry — V4 endpoints unique to the Go SDK
- WebSocket events stream (parity with `clever-client.js` `EventsStream`)
- OpenTelemetry bridge
- Resource-ID resolver / ownerId cache

## Authentication

### API token (recommended)

Tokens are minted from the Console (or via `$client->apiTokens->create()` with
an existing token). They go in `Credentials::apiToken()`, are sent as
`Authorization: Bearer <token>`, and the `apiTokens` resource itself routes
to `api-bridge.clever-cloud.com` so the gateway can validate the scopes.

### OAuth 1.0a (legacy)

Three-legged flow helper for the consumer + user token pattern:

```php
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Auth\OAuthFlow;

$flow = new OAuthFlow(new OAuth1Signer(), $psr18, $requestFactory);

$req  = $flow->requestToken($consumerKey, $consumerSecret, 'https://app.example/callback');
$url  = $flow->authorizationUrl($req['token']);          // redirect the user
$tok  = $flow->accessToken($consumerKey, $consumerSecret, $req['token'], $req['tokenSecret'], $verifier);

$credentials = Credentials::oauth1($consumerKey, $consumerSecret, $tok['token'], $tok['tokenSecret']);
```

## Configuration knobs

```php
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Http\RetryPolicy;

$client = (new ClientBuilder())
    ->withCredentials($credentials)
    ->withConfiguration(new Configuration(userAgent: 'my-app/1.0', timeoutSeconds: 15))
    ->withRetryPolicy(new RetryPolicy(maxAttempts: 5, baseDelayMs: 250))
    ->withLogger($psr3Logger)                  // optional PSR-3 logger
    ->withHttpClient($symfonyHttpClient)       // optional Symfony HttpClient override
    ->onRequest(fn ($req) => $req->withHeader('X-My-Trace', $traceId))
    ->onResponse(fn ($res, $req) => $metrics->record($res->getStatusCode(), $res->getHeaderLine('Sozu-Id')))
    ->build();
```

### Lifecycle hooks

`onRequest(Closure $hook): self` and `onResponse(Closure $hook): self` accept
multiple registrations and fire in order:

- **onRequest** receives a `Psr\Http\Message\RequestInterface` after URI / body
  construction but **before** authentication is applied. Returning a modified
  request swaps it for the rest of the pipeline (signing, retries, dispatch).
- **onResponse** receives `(ResponseInterface $response, RequestInterface $request)`
  on every response, success or error. Read-only — return value ignored.

Typical uses: tracing-header propagation, latency histograms, request-ID
correlation to a log aggregator.

### PSR-3 log context keys

When a `LoggerInterface` is registered, the SDK emits the following keys (use
them in dashboards / log filters):

| Key         | Level | Description                                                            |
| ----------- | ----- | ---------------------------------------------------------------------- |
| `attempt`   | debug, warning | 1-based attempt counter inside the retry loop.                  |
| `method`    | debug, warning, error | HTTP method.                                              |
| `uri`       | debug, warning, error | Final URI (after URI builder + query string).             |
| `status`    | debug, warning, error | HTTP status code.                                         |
| `requestId` | debug | Server request ID from `X-Request-Id`, `Sozu-Id`, or `X-Sozu-Id`.       |
| `delayMs`   | warning | Sleep duration before next retry.                                     |
| `exception` | warning, error | Exception class / message.                                     |

Channel: `clevercloud-sdk` (string prefix on all log messages). Levels: `debug`
on every response, `warning` on retries (rate-limit + 5xx), `error` on the
terminal failure that raises an exception.

## Error handling

Everything the SDK throws implements `CleverCloud\Sdk\Exception\CleverCloudException`.
HTTP errors land in typed subclasses of `ApiException`:

| Status               | Exception                |
| -------------------- | ------------------------ |
| 401 / 403            | `AuthException`          |
| 404                  | `NotFoundException`      |
| 400 / 422 + `errors` | `ValidationException`    |
| 429                  | `RateLimitException`     |
| 5xx                  | `ServerException`        |
| other 4xx            | `ApiException`           |
| PSR-18 transport     | `TransportException`     |
| bad SDK config       | `ConfigurationException` |

All `ApiException`s carry `$statusCode`, `$errorCode`, `$requestId`, and the
decoded `$body`. `ValidationException` adds a `field => list<message>` map,
`RateLimitException` adds `$retryAfterSeconds`.

## Testing your code against the SDK

The SDK's transport is `symfony/http-client`. Inject a `MockHttpClient` and
you get full control over what each call returns, with zero network IO:

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$mock = new MockHttpClient([
    new MockResponse(
        json_encode(['id' => 'app_42', 'name' => 'hello'], JSON_THROW_ON_ERROR),
        ['response_headers' => ['content-type' => 'application/json']],
    ),
]);

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken('test'))
    ->withHttpClient($mock)
    ->build();

$app = $client->applications->get('app_42');
self::assertSame('hello', $app->name);
```

See `examples/mocking.php` for a runnable example.

## License

[MIT](LICENSE)
