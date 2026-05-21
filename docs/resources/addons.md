# Add-ons (`/v2/.../addons`)

Source: [`src/Resource/V2/AddonsResource.php`](../../src/Resource/V2/AddonsResource.php)

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
| `plans()` | GET | `/v2/products/addonproviders/{id}/plans` (raw list) |

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

## Plan migrations

```php
public function migrate(string $addonId, string $targetPlanId, ?string $organisationId = null): Addon
public function listMigrations(string $addonId, ?string $organisationId = null): list<array<string, mixed>>
public function getMigration(string $addonId, string $migrationId, ?string $organisationId = null): array<string, mixed>
public function cancelMigration(string $addonId, string $migrationId, ?string $organisationId = null): void
public function preorderMigration(string $addonId, string $targetPlanId, ?string $organisationId = null): array<string, mixed>
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `migrate()` | POST | `/v2/.../addons/{id}/migrations` | `{"plan": "{targetPlanId}"}` |
| `listMigrations()` | GET | `/v2/.../addons/{id}/migrations` | — |
| `getMigration()` | GET | `/v2/.../addons/{id}/migrations/{migrationId}` | — |
| `cancelMigration()` | DELETE | `/v2/.../addons/{id}/migrations/{migrationId}` | — |
| `preorderMigration()` | POST | `/v2/.../addons/{id}/migrations/preorder` | `{"plan": "{targetPlanId}"}` |

`migrate()` returns the updated `Addon` immediately; track progress via
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

Source: [`src/Model/Addon.php`](../../src/Model/Addon.php). Notable fields:
`id`, `name`, `realId` (provider-side internal ID), `region`, `provider`
(nested `AddonProvider`), `plan` (nested `AddonPlan`), `configKeys`,
`creationDate` (ms).
