# Pulsar policies (`/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies`)

Source: [`src/Resource/V4/PulsarPoliciesResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/PulsarPoliciesResource.php)

Retention and offload settings for a Pulsar add-on. Topics and
clusters are covered by [`$client->pulsar`](pulsar.md).

## Access

```php
$client->pulsarPolicies
```

## Methods

```php
public function get(string $addonId): PulsarPolicy
public function update(string $addonId, array $policy): PulsarPolicy
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `get()` | GET | `/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies` | — |
| `update()` | PATCH | `/v4/addon-providers/addon-pulsar/addons/{id}/storage-policies` | `{"retentionPolicies"?: {...}, "offloadPolicies"?: {...}}` |

`update()` sets the namespace retention policy and, when `offloadPolicies` is
sent, its offload (cold storage) thresholds. Each side is
`{"durationInMinutes": int|null, "sizeInBytes": int|null}`: sizes in bytes,
durations in minutes. It returns the policies the cluster then applies.

## `PulsarPolicy` DTO

See [`src/Model/PulsarPolicy.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/PulsarPolicy.php).
`retentionPolicies` and `offloadPolicies` are `?PulsarStorageLimit` objects
(`durationInMinutes`, `sizeInBytes`), null when no limit is set. The API sends
no add-on id: `addonId` is filled by the SDK from the requested add-on.
