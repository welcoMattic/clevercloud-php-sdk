# Add-on backups (`/v4/addon-providers/{providerId}/addons/{addonId}/backups`)

Source: [`src/Resource/V4/BackupsResource.php`](../../src/Resource/V4/BackupsResource.php)

## Access

```php
$client->backups
```

The `$providerId` is the addon provider type (`postgresql-addon`,
`mongodb-addon`, …); `$addonId` is the **real** add-on id (the `realId`
field on the `Addon` DTO — `postgresql_xxx`, not `addon_xxx`).

## Methods

```php
public function list(string $providerId, string $addonId): list<Backup>
public function get(string $providerId, string $addonId, string $backupId): Backup
public function restore(string $providerId, string $addonId, string $backupId): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v4/addon-providers/{providerId}/addons/{addonId}/backups` |
| `get()` | GET | `/v4/addon-providers/{providerId}/addons/{addonId}/backups/{backupId}` |
| `restore()` | POST | `/v4/addon-providers/{providerId}/addons/{addonId}/backups/{backupId}/restore` (no body) |

`restore()` semantics (in-place vs new instance) depend on the provider —
see Clever Cloud's docs per add-on.

## `Backup` DTO

See [`src/Model/Backup.php`](../../src/Model/Backup.php). Fields:
`id`, `createdAt`, `status`, `downloadUrl` (pre-signed, expires within
minutes), `size`, `type`.
