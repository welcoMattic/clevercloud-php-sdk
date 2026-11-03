<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/assets/clevercloud-logo-dark.svg">
    <img src="docs/assets/clevercloud-logo.svg" alt="Clever Cloud" width="320">
  </picture>
</p>

# Clever Cloud PHP SDK

A modern PHP SDK for the [Clever Cloud](https://www.clever-cloud.com) REST API
(v2 + v4 + api-bridge), aligned with the
[unified OpenAPI spec](https://www.clever.cloud/developers/api/) Clever Cloud
publishes ([JSON](https://api.clever-cloud.com/openapi.json)) and checked
against the live API.

> Status: **v2.0**. Public API surface is stable; changes that break
> source compatibility will trigger a major bump per
> [semver](https://semver.org).

## Requirements

- PHP **8.5+**
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

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken(getenv('CC_API_TOKEN')))
    ->build();

$me = $client->self->get();
echo $me->email, "\n";
```

Mint your token from the [Clever Cloud Console](https://console.clever-cloud.com/) (section "Personal API tokens"), set it as `CC_API_TOKEN`, and you're done.

The SDK also supports OAuth 1.0a for legacy consumers and 3-legged authorisation flows - see [`docs/authentication.md`](docs/authentication.md) for the full picture.

## Coverage matrix

The SDK covers most of Clever Cloud's v2 + v4 surface, plus the api-bridge
gateway used for API tokens. Below is the actual coverage shipped in v2.0; what
is deliberately out of scope at this stage is listed in the "Roadmap" section.

### V2 — application platform

| Family          | Methods on `$client->...`                                                                                                                                                                |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `self` | `get / update / sshKeys / addSshKey / removeSshKey / emailAddresses / addEmailAddress / removeEmailAddress / consumers / getConsumer / createConsumer / updateConsumer / deleteConsumer / consumerSecret / consumerTokens / startMfa / confirmMfa / disableMfa / mfaStatus / setFavouriteMfa / regenerateMfaBackupCodes / changePassword / id / setAvatar / resendConfirmationEmail / validateEmail / organisations / instances / paymentInfo / tokens / currentToken / revokeToken / revokeAllTokens / cliTokens` |
| `users` | `get / applications` |
| `organisations` | `list / get / create / update / delete / members / addMember / updateMember / removeMember / invitations / cancelInvitation / consumers / getConsumer / createConsumer / updateConsumer / deleteConsumer / consumerSecret / namespaces / setAvatar / paymentInfo / drainPolicy / deployments / instances` |
| `applications` | `list / get / create / update / delete / restart / stop / deploy / branches / setBranch / setBuildFlavor / instances / dependencies / addDependency / removeDependency / dependents / dependenciesEnv / tags / addTag / removeTag / replaceTags / exposedEnv / setExposedEnv / addons / linkAddon / unlinkAddon / addonsEnv` |
| `addons` | `list / get / create / update / delete / providers / provider / plans / linkedApplications / env / sso / generateSso / tags / addTag / removeTag / replaceTags / preorder / migrate / listMigrations / getMigration / cancelMigration / preorderMigration / instances / instance` |
| `deployments`   | `list / get / cancel / instances`                                                                                                                                                        |
| `environment`   | `list / get / set / setMany / remove`                                                                                                                                                    |
| `domains`       | `list / add / remove / favourite / setFavourite / unsetFavourite`                                                                                                                        |
| `tcpRedirections` | `list / namespaces / add / remove`                                                                                                                                                     |
| `notifications` | `list / create / delete` (email hooks) |
| `webhooks` | `list / create / delete` |
| `backups` | `list` |
| `instances` | `list` (instance types catalogue) |
| `serviceTokens` | `list / listPage / create / get / delete` |
| `events` | `stream` (SSE `/v2/events`) |

### Root (unversioned paths)

| Family | Methods on `$client->...` |
| --- | --- |
| `logExports` | `list / create / get / delete / download` |

### V4 — platform extensions

| Family            | Methods on `$client->...`                                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `billing` | `getBalance / listInvoices / getInvoice / downloadInvoicePdf / paymentMethods / addPaymentMethod / removePaymentMethod` |
| `loadBalancers` | `list` |
| `products`        | `instances / addonProviders / zones / countries`                                                                         |
| `zones`           | `list / get`                                                                                                             |
| `pulsarPolicies`  | `get / update`                                                                                                           |
| `logs` | `stream` (SSE → `LogStream<LogEntry>`) / `query` (historical) / `streamResource / queryResource / streamAccessLogs / queryAccessLogs / streamResourceAccessLogs / queryResourceAccessLogs` |
| `kubernetes` | `listClusters / getCluster / createCluster / updateCluster / deleteCluster / redeploy / resume / reconcilePlugins / migrateSni / checkVersion / updateVersion / kubeconfig / kubeconfigPresignedUrl / events / deploymentEvents / listNodeGroups / getNodeGroup / createNodeGroup / updateNodeGroup / deleteNodeGroup / resumeNodeGroup / listNodes / getNode / createNode / deleteNode / quota / usage / zones / pluginCatalog / serviceConfig / enableCephCsi` |
| `pulsar` | `topics / createTopic / deleteTopic / createTopicToken / unloadTopic / cluster` |
| `configProviders` | `get / env / replaceEnv` |
| `materiaKv` | `providerInformation / get` |
| `addonProviders` | `getAddon` |
| `operators` | `.keycloak / .matomo / .metabase / .otoroshi` (each: `providerInformation / get / reboot / rebuild`; Keycloak, Metabase, Otoroshi: `checkVersion / updateVersion`; Keycloak, Otoroshi: `linkNetworkGroup / unlinkNetworkGroup`; Otoroshi: `configYaml`). Create and delete operators through `addons` |
| `drains` | `list / get / create / delete / enable / disable / restart` |
| `networkGroups` | `configuration / list / search / get / create / delete / stream / members / member / addMember / removeMember / peers / peer / addPeer / removePeer / externalPeer / addExternalPeer / removeExternalPeer / wireguardConfiguration / wireguardConfigurationPresignedUrl / streamWireguardConfiguration` |
| `orchestration` | `instances / deployments / getDeployment` |

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
| `DrainType` | BetterStack / Datadog / ElasticSearch / NewRelic / OvhTcp / RawHttp / Splunk / SyslogTcp / SyslogUdp | Log drain recipient protocol |
| `DrainKind` | Log / AccessLog | Log stream a drain ships |
| `ServiceTokenRole` | `Admin Manager Developer Accounting` | Service token role |
| `ServiceTokenStatus` | `Active Expired Revoked` | Service token lifecycle |
| `LogExportStatus` | (see source, + `isTerminal()`) | Log export lifecycle |
| `KubernetesClusterStatus` | (see source) | Kubernetes cluster lifecycle |
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
$client->products->countries();    // -> array<string, string>
$client->addons->providers();      // -> list<AddonProvider> (postgresql-addon, redis-addon, …)
$client->addons->provider($id);    // -> AddonProvider       (with its plans)
```

### Roadmap

- AI, Materia TS, Cellar, Cumulocity, DNS, IPAM, Function, Container Registry:
  V4 endpoints outside the published spec
- WebSocket events stream (`/v2/events/event-socket`); the SSE stream is
  covered by `$client->events`
- OpenTelemetry bridge

## Authentication

### API token (recommended)

Tokens are minted from the Console (or via `$client->apiTokens->create()` with
an existing OAuth 1.0a client). They go in `Credentials::apiToken()`, are sent as
`Authorization: Bearer <token>`, and the `apiTokens` resource itself routes
to `api-bridge.clever-cloud.com` so the gateway can validate the scopes. Note
that the `apiTokens` resource **requires OAuth 1.0a authentication** - you cannot
manage tokens with a Bearer token.

### Service token (biscuit)

Organisation service tokens are minted with `$client->serviceTokens->create()`
(from an OAuth 1.0a client) and used with `Credentials::serviceToken()`. They
are sent as `Authorization: Bearer <token>` straight to `api.clever-cloud.com`
and are accepted on organisation-scoped routes only, never under `/v2/self`.
See [Authentication](docs/authentication.md).

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

## Sponsors

If this project is useful to you, you can support my open source work on [GitHub Sponsors](https://github.com/sponsors/welcoMattic). Tiers and what they fund: [blog.welcomattic.com/sponsors/en](https://blog.welcomattic.com/sponsors/en/). From the Company tier ($100 a month), your logo and a link appear here.

## License

[MIT](LICENSE)
