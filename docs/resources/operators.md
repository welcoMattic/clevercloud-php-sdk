# Operators — Keycloak / Matomo / Metabase / Otoroshi

Source: [`src/Resource/V4/OperatorsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/OperatorsResource.php) (facade) and [`src/Resource/V4/AbstractOperatorResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/AbstractOperatorResource.php) (shared base)

Clever Cloud's "operators" are fully-managed add-ons for popular open-source
services. The SDK exposes them through a facade with four typed sub-clients
that share the same read and lifecycle surface.

The operator endpoints only read an existing add-on and act on it: the API
has no route to list, create, update or delete one there. Create and delete
operators through [`$client->addons`](addons.md) like any other add-on
(provider `keycloak`, `addon-matomo`, `metabase` or `otoroshi`), then use the
`resourceId` of the result (`keycloak_…`, `matomo_…`, …) with these
sub-clients.

## Access

```php
$client->operators->keycloak     // KeycloakResource
$client->operators->matomo       // MatomoResource
$client->operators->metabase     // MetabaseResource
$client->operators->otoroshi     // OtoroshiResource
```

Each sub-client extends `AbstractOperatorResource` so they share the same
method surface, only the path slug differs:

| Sub-client | Path slug |
| --- | --- |
| `keycloak` | `addon-keycloak` |
| `matomo` | `addon-matomo` |
| `metabase` | `addon-metabase` |
| `otoroshi` | `addon-otoroshi` |

## Methods (all four sub-clients)

```php
public function get(string $id): Operator
public function reboot(string $id): void
public function rebuild(string $id): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `get()` | GET | `/v4/addon-providers/addon-{slug}/addons/{id}` |
| `reboot()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/reboot` |
| `rebuild()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/rebuild` |

## Network Group link (Keycloak, Otoroshi)

Matomo and Metabase have no Network Group route, so these methods live on the
`keycloak` and `otoroshi` sub-clients only.

```php
public function linkNetworkGroup(string $id): void
public function unlinkNetworkGroup(string $id): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `linkNetworkGroup()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/networkgroup` |
| `unlinkNetworkGroup()` | DELETE | `/v4/addon-providers/addon-{slug}/addons/{id}/networkgroup` |

## Provider information (all four sub-clients)

```php
public function providerInformation(): AddonProviderInformation
```

Returns the provider as the console shows it: the clusters add-ons are
created on and the dedicated versions on offer. The API paths are not
consistent across operators:

| Sub-client | HTTP | Path |
| --- | --- | --- |
| `keycloak` | GET | `/v4/addon-providers/keycloak` |
| `matomo` | GET | `/v4/addon-providers/addon-matomo` |
| `metabase` | GET | `/v4/addon-providers/metabase` |
| `otoroshi` | GET | `/v4/addon-providers/otoroshi` |

`AddonProviderInformation` exposes `providerId`, `defaultDedicatedVersion`,
`clusters` (a list of `AddonProviderCluster`: `id`, `label`, `zone`,
`version`, `features`) and `dedicated` (dedicated versions keyed by version,
as raw arrays).

## Version management (Keycloak, Metabase, Otoroshi)

Matomo has no version endpoints, so these methods live on the `keycloak`,
`metabase` and `otoroshi` sub-clients only.

```php
public function checkVersion(string $id): OperatorVersionCheck
public function updateVersion(string $id, string $targetVersion): array
```

| Method | HTTP | Path |
| --- | --- | --- |
| `checkVersion()` | GET | `/v4/addon-providers/addon-{slug}/addons/{id}/version/check` |
| `updateVersion()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/version/update` (body: `{"targetVersion": "..."}`) |

`OperatorVersionCheck` exposes `available` (versions on offer), `installed`,
`latest`, `needUpdate` and, for Metabase only, `requiresApplicationUpdate`
(whether moving to `latest` also resizes the add-on's application).

`updateVersion()` returns the updated add-on as decoded JSON: its shape is
specific to each operator.

```php
$check = $client->operators->keycloak->checkVersion('keycloak_xxx');
if ($check->needUpdate) {
    $client->operators->keycloak->updateVersion('keycloak_xxx', $check->latest);
}
```

## Otoroshi configuration file

```php
public function configYaml(string $id): string
```

| Method | HTTP | Path |
| --- | --- | --- |
| `configYaml()` | GET | `/v4/addon-providers/addon-otoroshi/addons/{id}/config.yaml` |

Returns the raw YAML document (`Accept: application/yaml`); parse it with
`symfony/yaml` or any YAML parser if you need a structure.

## `Operator` DTO

Shared payload type across the four operators. See
[`src/Model/Operator.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Operator.php).

The API sends no `id` field. `$id` is read from `resourceId`, the operator id
(`keycloak_…`, `matomo_…`, …) that `get()`, `reboot()` and the other
per-operator methods take. `$addonId` is the matching `addon_…` id used by
`$client->addons`. `$ownerId`, `$version` and `$accessUrl` are mapped too.

## Note on ownership

The API resolves ownership from your credentials — you don't pass an
organisation id on these endpoints (unlike most V2 / V4 routes).
