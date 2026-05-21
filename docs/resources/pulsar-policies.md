# Pulsar policies (`/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies`)

Source: [`src/Resource/V4/PulsarPoliciesResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/PulsarPoliciesResource.php)

Retention, offload, and TTL settings for a Pulsar add-on.

## Access

```php
$client->pulsarPolicies
```

## Methods

```php
public function get(string $addonId): PulsarPolicy
public function update(string $addonId, array $policy): PulsarPolicy
public function reset(string $addonId): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `get()` | GET | `/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies` | — |
| `update()` | PATCH | `/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies` | JSON of fields to merge |
| `reset()` | DELETE | `/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies` | — |

`update()` is **partial** — only the fields you pass are touched. `reset()`
restores provider defaults.

## `PulsarPolicy` DTO

See [`src/Model/PulsarPolicy.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/PulsarPolicy.php). Fields
include `addonId`, `namespace`, `retentionTimeInMinutes`,
`retentionSizeInMB`, etc.
