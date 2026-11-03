# Add-ons (`/v2/.../addons`)

Source: [`src/Resource/V2/AddonsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/AddonsResource.php)

## Access

```php
$client->addons
```

Every method that mutates an add-on takes an optional `?string $organisationId`.
`null` scopes to `/self`, otherwise to `/organisations/{ownerId}`.

## CRUD

```php
public function list(?string $organisationId = null): list<Addon>
public function get(string $addonId, ?string $organisationId = null): Addon
public function create(array $data, ?string $organisationId = null): Addon
public function update(string $addonId, array $data, ?string $organisationId = null): Addon
public function delete(string $addonId, ?string $organisationId = null): void
```

`create()` minimal payload (from the source's docblock):

```php
$client->addons->create([
    'name'       => 'my-pg',
    'region'     => 'par',
    'providerId' => 'postgresql-addon',
    'plan'       => 'plan_xxx',   // provider-specific plan ID
]);
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v2/.../addons` |
| `get()` | GET | `/v2/.../addons/{id}` |
| `create()` | POST | `/v2/.../addons` (JSON body) |
| `update()` | PUT | `/v2/.../addons/{id}` (JSON body) |
| `delete()` | DELETE | `/v2/.../addons/{id}` |

## Catalogue (no auth scope, top-level)

```php
public function providers(): list<AddonProvider>
public function provider(string $providerId): AddonProvider
public function plans(string $providerId): list<array<string, mixed>>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `providers()` | GET | `/v2/products/addonproviders` |
| `provider()` | GET | `/v2/products/addonproviders/{id}` (includes plans) |
| `plans()` | GET | `/v2/products/addonproviders/{id}` (returns nested `plans` key) |

These are the same routes as `$client->products->addonProviders()` — kept
on the addons resource for discoverability when you're already in the
add-on flow.

## Connection environment & links

```php
public function env(string $addonId, ?string $organisationId = null): array<string, string>
public function linkedApplications(string $addonId, ?string $organisationId = null): list<array<string, mixed>>
public function sso(string $addonId, ?string $organisationId = null): array<string, mixed>
```

| Method | HTTP | Path | Returns |
| --- | --- | --- | --- |
| `env()` | GET | `/v2/.../addons/{id}/env` | `name => value` map (the SDK collapses the API's `[{name, value}]` list) |
| `linkedApplications()` | GET | `/v2/.../addons/{id}/applications` | Raw list of linked app payloads |
| `sso()` | GET | `/v2/.../addons/{id}/sso` | Raw SSO payload (signed URL + params) for Pulsar / Cellar / Matomo etc. |

```php
public function generateSso(string $addonId, ?string $organisationId = null): array<string, mixed>
```

`POST /v2/.../addons/{id}/sso` issues fresh single-sign-on data, where
`sso()` reads it. The raw payload holds `id`, `token`, `signature`,
`timestamp`, `email`, `user_id`, `nav-data` and an optional `url`.

## Order preview

```php
public function preorder(array $data, ?string $organisationId = null): array<string, mixed>
```

`POST /v2/.../addons/preorders` takes the same payload as `create()` and
returns the raw invoice preview without creating the add-on.

## Instances (organisations only)

```php
public function instances(string $addonId, string $organisationId): list<Instance>
public function instance(string $addonId, string $instanceId, string $organisationId): Instance
```

| Method | HTTP | Path |
| --- | --- | --- |
| `instances()` | GET | `/v2/organisations/{ownerId}/addons/{id}/instances` |
| `instance()` | GET | `/v2/organisations/{ownerId}/addons/{id}/instances/{instanceId}` |

`Instance` exposes `id`, `appId`, `state`, `displayName`, `ip`, `appPort`,
`commit`, `deployId`, `deployNumber`, `instanceNumber`, `creationDate` and
`flavor` (raw array). The API only documents these routes for
organisations, so `$organisationId` is required.

## Tags

```php
public function tags(string $addonId, ?string $organisationId = null): list<string>
public function addTag(string $addonId, string $tag, ?string $organisationId = null): void
public function removeTag(string $addonId, string $tag, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `tags()` | GET | `/v2/.../addons/{id}/tags` |
| `addTag()` | PUT | `/v2/.../addons/{id}/tags/{tag}` |
| `removeTag()` | DELETE | `/v2/.../addons/{id}/tags/{tag}` |

```php
public function replaceTags(string $addonId, array $tags, string $organisationId): list<string>
```

`PUT /v2/organisations/{ownerId}/addons/{id}/tags`, body
`["tag", "key:value"]`, replaces all the tags and returns the resulting
ones. Organisations only, like the API documents it.

## Plan migrations

```php
public function migrate(string $addonId, string $targetPlanId, ?string $organisationId = null, ?string $region = null, ?string $version = null): Addon
public function listMigrations(string $addonId, ?string $organisationId = null): list<array<string, mixed>>
public function getMigration(string $addonId, string $migrationId, ?string $organisationId = null): array<string, mixed>
public function cancelMigration(string $addonId, string $migrationId, ?string $organisationId = null): void
public function preorderMigration(string $addonId, string $targetPlanId, ?string $organisationId = null): array<string, mixed>
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `migrate()` (organisation) | POST | `/v2/organisations/{orgId}/addons/{id}/migrations` | `{"planId": "...", "region": "...", "version"?: "..."}` |
| `migrate()` (`/self`) | POST | `/v2/self/addons/{id}/migration` | `{"planId": "...", "region": "...", "version"?: "..."}` |
| `listMigrations()` | GET | `/v2/organisations/{ownerId}/addons/{id}/migrations` | - |
| `getMigration()` | GET | `/v2/organisations/{ownerId}/addons/{id}/migrations/{migrationId}` | - |
| `cancelMigration()` | DELETE | `/v2/organisations/{ownerId}/addons/{id}/migrations/{migrationId}` | - |
| `preorderMigration()` | GET | `/v2/organisations/{ownerId}/addons/{id}/migrations/preorders?planId={targetPlanId}` | - |

The API requires the target `region` on a plan change. When `$region` is null,
`migrate()` reads the add-on's current region first (one extra `GET`).

The API serves migration tracking and cost previews under
`/v2/organisations/{ownerId}` only: `/v2/self/addons/{id}/migrations` answers
404. With a `null` `$organisationId`, `listMigrations()`, `getMigration()`,
`cancelMigration()` and `preorderMigration()` resolve the personal
organisation id (`user_<uuid>`) through `GET /v2/self`, costing one extra
request. Pass that id yourself to skip the lookup.

`preorderMigration()` returns the invoice preview of the plan change (price
difference with the current plan) as a raw array.

`migrate()` maps the response to an `Addon`. The API forwards the add-on
provider's answer, whose format is provider-defined. Track progress via
`listMigrations()` / `getMigration()`. Use `MigrationStatus` enum to branch
on the `status` field:

```php
use CleverCloud\Sdk\Model\Enum\MigrationStatus;

foreach ($client->addons->listMigrations($addonId) as $migration) {
    $status = MigrationStatus::tryFrom($migration['status'] ?? '');
    if ($status?->isTerminal()) {
        // success / failed / cancelled
    }
}
```

## `Addon` DTO

Source: [`src/Model/Addon.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Addon.php). Notable fields:
`id`, `name`, `realId` (provider-side internal ID), `region`, `provider`
(nested `AddonProvider`), `plan` (nested `AddonPlan`), `configKeys`,
`creationDate` (ms).
