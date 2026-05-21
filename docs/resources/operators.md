# Operators — Keycloak / Matomo / Metabase / Otoroshi

Source: [`src/Resource/V4/OperatorsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/OperatorsResource.php) (facade) and [`src/Resource/V4/AbstractOperatorResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/AbstractOperatorResource.php) (shared CRUD)

Clever Cloud's "operators" are fully-managed add-ons for popular open-source
services. The SDK exposes them through a facade with four typed sub-clients
that share the same CRUD surface.

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
public function list(): list<Operator>
public function get(string $id): Operator
public function create(array $data): Operator
public function update(string $id, array $data): Operator
public function delete(string $id): void
public function reboot(string $id): void
public function rebuild(string $id): void
public function linkNetworkGroup(string $id): void
public function unlinkNetworkGroup(string $id): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v4/addon-providers/addon-{slug}/addons` |
| `get()` | GET | `/v4/addon-providers/addon-{slug}/addons/{id}` |
| `create()` | POST | `/v4/addon-providers/addon-{slug}/addons` (body: `{"name", "region", "planId", ...}`) |
| `update()` | PUT | `/v4/addon-providers/addon-{slug}/addons/{id}` |
| `delete()` | DELETE | `/v4/addon-providers/addon-{slug}/addons/{id}` |
| `reboot()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/reboot` |
| `rebuild()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/rebuild` |
| `linkNetworkGroup()` | POST | `/v4/addon-providers/addon-{slug}/addons/{id}/networkgroup` |
| `unlinkNetworkGroup()` | DELETE | `/v4/addon-providers/addon-{slug}/addons/{id}/networkgroup` |

`linkNetworkGroup()` and `unlinkNetworkGroup()` are only meaningful for
Keycloak and Otoroshi (per the source's docblock).

## `Operator` DTO

Shared payload type across the four operators. See
[`src/Model/Operator.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Operator.php).

## Note on ownership

The API resolves ownership from your credentials — you don't pass an
organisation id on these endpoints (unlike most V2 / V4 routes).
