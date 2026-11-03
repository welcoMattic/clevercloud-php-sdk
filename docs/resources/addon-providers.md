# Add-on providers (`/v4/addon-providers/{providerId}`)

Source: [`src/Resource/V4/AddonProvidersResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/AddonProvidersResource.php)

Provider-agnostic add-on endpoints.

## Access

```php
$client->addonProviders
```

## Methods

```php
public function getAddon(string $providerId, string $realId): array
```

| Method | HTTP | Path |
| --- | --- | --- |
| `getAddon()` | GET | `/v4/addon-providers/{providerId}/addons/{realId}` |

`getAddon()` returns the add-on as its provider describes it. Each provider
defines its own shape, so the payload is returned as decoded JSON.

`$providerId` is the add-on provider (`postgresql-addon`, `addon-pulsar`,
...); `$realId` is the **real** add-on id (the `realId` field on the `Addon`
DTO, `postgresql_xxx`, not `addon_xxx`).
