# Applications

Source: [`src/Resource/V2/ApplicationsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/ApplicationsResource.php)

Every method takes an optional `?string $organisationId`. When `null`, the
SDK scopes the call to `/self` (your own resources); otherwise it scopes to
`/organisations/{ownerId}`.

## Access

```php
$client->applications
```

## CRUD

| Method | HTTP | Path |
| --- | --- | --- |
| `list(?string $organisationId = null): list<Application>` | GET | `/v2/.../applications` |
| `get(string $applicationId, ?string $organisationId = null): Application` | GET | `/v2/.../applications/{id}` |
| `create(array $data, ?string $organisationId = null): Application` | POST | `/v2/.../applications` |
| `update(string $applicationId, array $data, ?string $organisationId = null): Application` | PUT | `/v2/.../applications/{id}` |
| `delete(string $applicationId, ?string $organisationId = null): void` | DELETE | `/v2/.../applications/{id}` |

`create()` and `update()` take an `array<string, mixed>` payload. Minimal
create shape (from the source's docblock):

```php
$client->applications->create([
    'name'            => 'my-app',
    'deploy'          => 'git',       // or 'ftp', 'docker'
    'instanceType'    => 'node',
    'instanceVariant' => '20',
    'zone'            => 'par',
    'minInstances'    => 1,
    'maxInstances'    => 1,
    'minFlavor'       => 'nano',
    'maxFlavor'       => 'nano',
]);
```

Use the [`Flavor`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Enum/Flavor.php) and
[`DeployType`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Enum/DeployType.php) enums to avoid magic
strings:

```php
use CleverCloud\Sdk\Model\Enum\Flavor;
use CleverCloud\Sdk\Model\Enum\DeployType;

$client->applications->create([
    'name'      => 'my-app',
    'deploy'    => DeployType::Git->value,
    'minFlavor' => Flavor::Nano->value,
    // ...
]);
```

## Lifecycle

```php
public function restart(string $applicationId, ?string $organisationId = null, bool $withoutCache = false): void
public function deploy(string $applicationId, ?string $commit = null, ?string $organisationId = null): void
public function stop(string $applicationId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Notes |
| --- | --- | --- | --- |
| `restart()` | POST | `/v2/.../applications/{id}/instances` | `?useCache=no` when `$withoutCache = true` |
| `deploy()` | POST | `/v2/.../applications/{id}/instances` | `?commit={sha}` when `$commit` is set |
| `stop()` | DELETE | `/v2/.../applications/{id}/instances` | |

Both `restart()` and `deploy()` send an empty JSON body (`{}`) with
`Content-Type: application/json` — Clever Cloud's API requires a media
type even when there's no payload.

## Git branch

```php
public function setBranch(string $applicationId, string $branch, ?string $organisationId = null): void
public function branches(string $applicationId, ?string $organisationId = null): list<string>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `setBranch()` | PUT | `/v2/.../applications/{id}/branch` (body: `{"branch": "..."}`) |
| `branches()` | GET | `/v2/.../applications/{id}/branches` (response: list of strings) |

`branches()` returns an empty list for non-git deploy targets, or apps that
haven't been pushed yet.

## Running instances

```php
public function instances(string $applicationId, ?string $organisationId = null): list<array<string, mixed>>
```

`GET /v2/.../applications/{id}/instances` — raw payload, not yet mapped to
a DTO. Each entry contains `id`, `state`, `flavor`, `commit`, `zone`, etc.

## Dependencies

Linked applications whose exposed env gets merged into this app.

```php
public function dependencies(string $applicationId, ?string $organisationId = null): list<array<string, mixed>>
public function addDependency(string $applicationId, string $dependencyAppId, ?string $organisationId = null): void
public function removeDependency(string $applicationId, string $dependencyAppId, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `dependencies()` | GET | `/v2/.../applications/{id}/dependencies` |
| `addDependency()` | PUT | `/v2/.../applications/{id}/dependencies/{dependencyId}` |
| `removeDependency()` | DELETE | `/v2/.../applications/{id}/dependencies/{dependencyId}` |

## Tags

```php
public function tags(string $applicationId, ?string $organisationId = null): list<string>
public function addTag(string $applicationId, string $tag, ?string $organisationId = null): void
public function removeTag(string $applicationId, string $tag, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `tags()` | GET | `/v2/.../applications/{id}/tags` |
| `addTag()` | PUT | `/v2/.../applications/{id}/tags/{tag}` |
| `removeTag()` | DELETE | `/v2/.../applications/{id}/tags/{tag}` |

## Exposed environment

Env vars exposed to apps that declare this one as a dependency.

```php
public function exposedEnv(string $applicationId, ?string $organisationId = null): array<string, string>
public function setExposedEnv(string $applicationId, array $variables, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `exposedEnv()` | GET | `/v2/.../applications/{id}/exposed_env` (returns `name => value` map) |
| `setExposedEnv()` | PUT | `/v2/.../applications/{id}/exposed_env` (body: `[{name, value}, ...]`) |

The SDK collapses the API's `[{name, value}]` list into a `name => value`
map on read, and expands it back on write.

## Linked add-ons

```php
public function addons(string $applicationId, ?string $organisationId = null): list<array<string, mixed>>
public function linkAddon(string $applicationId, string $addonId, ?string $organisationId = null): void
public function unlinkAddon(string $applicationId, string $addonId, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `addons()` | GET | `/v2/.../applications/{id}/addons` |
| `linkAddon()` | POST | `/v2/.../applications/{id}/addons` (body: `{"addon_id": "..."}`) |
| `unlinkAddon()` | DELETE | `/v2/.../applications/{id}/addons/{addonId}` |

## `Application` DTO

Source: [`src/Model/Application.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Application.php).

```php
public string  $id;
public string  $name;
public ?string $description;
public ?string $zone;
public ?string $branch;
public ?string $ownerId;
public ?string $state;          // see ApplicationState enum
public ?string $commitId;
public ?string $webhookUrl;
public ?bool   $archived;
public ?bool   $favourite;
public ?bool   $homogeneous;
public ?bool   $cancelOnPush;
public ?bool   $separateBuild;
public ?bool   $stickySessions;
public ?int    $creationDate;   // ms since epoch
public ?int    $lastDeploy;     // ms since epoch
public array   $vhosts;         // list<Vhost>
public array   $instance;       // raw array — instance/scaling config
public array   $deployment;     // raw array — deployment config
public array   $buildFlavor;    // raw array — build flavor info
```

`$state` matches one of the `ApplicationState` enum cases. Use
`ApplicationState::tryFrom($app->state)` to parse safely and
`isStable()` / `isTransient()` to drive UI logic.
