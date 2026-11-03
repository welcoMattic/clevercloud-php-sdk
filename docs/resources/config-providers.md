# Configuration providers (`/v4/addon-providers/config-provider/addons/{addonId}`)

Source: [`src/Resource/V4/ConfigProvidersResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/ConfigProvidersResource.php)

Reads and replaces the environment variables held by a configuration
provider add-on.

## Access

```php
$client->configProviders
```

`$addonId` is the real add-on id (`config_xxx`).

## Methods

```php
public function get(string $addonId): ConfigProvider
public function env(string $addonId): array<string, string>
public function replaceEnv(string $addonId, array<string, string> $variables): array<string, string>
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `get()` | GET | `/v4/addon-providers/config-provider/addons/{addonId}` | none |
| `env()` | GET | `/v4/addon-providers/config-provider/addons/{addonId}/env` | none |
| `replaceEnv()` | PUT | `/v4/addon-providers/config-provider/addons/{addonId}/env` | `[{"name", "value"}, ...]` |

Variables are exposed as a `name => value` map, like
[`$client->environment`](environment.md); the SDK converts from and to the
API's list of `{name, value}` objects.

`replaceEnv()` replaces the whole set: a variable missing from the map is
removed. It returns the resulting variables.

```php
$env = $client->configProviders->env('config_xxx');
$env['FEATURE_FLAG'] = 'on';
$client->configProviders->replaceEnv('config_xxx', $env);
```

## `ConfigProvider` DTO

See [`src/Model/ConfigProvider.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/ConfigProvider.php). Fields:
`id`, `creationDate` (RFC 3339 string), `ownerId`, `env` (`name => value`
map).
