# Add-on backups (`/v2/backups/{ownerId}/{realId}`)

Source: [`src/Resource/V2/BackupsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/BackupsResource.php)

## Access

```php
$client->backups
```

`$realId` is the **real** add-on id (the `realId` field on the `Addon` DTO,
`postgresql_xxx`, not `addon_xxx`). The route has no `/self` alias: with a
`null` `$organisationId` the SDK first reads the personal organisation id
(`user_xxx`) from `GET /v2/self`, so the call costs two requests.

## Methods

```php
public function list(string $realId, ?string $organisationId = null): list<Backup>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v2/backups/{ownerId}/{realId}` |

The API only lists backups: there is no route to read a single backup nor to
restore one. Restore a backup with the database's own tooling from the file
at `downloadUrl`.

## `Backup` DTO

See [`src/Model/Backup.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Backup.php). Fields:
`id`, `createdAt`, `entityId`, `status`, `deleteAt`, `filename`,
`downloadUrl` (pre-signed, short-lived).
