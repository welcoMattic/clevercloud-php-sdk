# Zones (`/v4/products/zones`)

Source: [`src/Resource/V4/ZonesResource.php`](../../src/Resource/V4/ZonesResource.php)

Thin alias around `$client->products->zones()` with an extra `get($name)`
endpoint. Returns deployment zones (`par`, `mtl`, `rbx`, `scw`, …).

## Access

```php
$client->zones
```

## Methods

```php
public function list(): list<Zone>
public function get(string $name): Zone
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v4/products/zones` |
| `get()` | GET | `/v4/products/zones/{name}` |

`{name}` is the short zone code (`par`, `mtl`, …), not the full city name.

## `Zone` DTO

Fields (verified against [`src/Model/Zone.php`](../../src/Model/Zone.php)):

```php
public string $name;                    // 'par', 'mtl', ...
public ?string $id;                     // UUID
public ?string $city;
public ?string $country;
public ?string $countryCode;
public ?string $displayName;
public ?float  $lat;
public ?float  $lon;
public array   $tags;                   // list<string> — e.g. ['for:applications', 'infra:ovh', 'green']
public array   $outboundIPs;            // list<string> — CIDR ranges
```
