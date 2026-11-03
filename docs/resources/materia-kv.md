# Materia KV

Source: [`src/Resource/V4/MateriaKvResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/MateriaKvResource.php)

Reads Materia KV databases and the Materia KV provider.

## Access

```php
$client->materiaKv
```

## Methods

```php
public function providerInformation(): AddonProviderInformation
public function get(string $ownerId, string $kvId): MateriaKvDatabase
```

| Method | HTTP | Path |
| --- | --- | --- |
| `providerInformation()` | GET | `/v4/addon-providers/kv` |
| `get()` | GET | `/v4/materia/organisations/{ownerId}/materia/databases/{kvId}` |

`providerInformation()` returns the cluster add-ons are created on (same DTO
as the [operators](operators.md#provider-information-all-four-sub-clients)).
The endpoint requires no authentication.

`get()` takes the owner id (an organisation `orga_xxx` or a user
`user_xxx`: the path says `organisations` for both) and the real add-on id
(`kv_xxx`).

## `MateriaKvDatabase` DTO

See [`src/Model/MateriaKvDatabase.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/MateriaKvDatabase.php). Fields:
`id`, `clusterId`, `ownerId`, `kind`, `plan`, `host`, `port`, `token`,
`tokenId`, `status`.

`token` is also the password of the Redis-compatible endpoint: treat it as a
secret.
