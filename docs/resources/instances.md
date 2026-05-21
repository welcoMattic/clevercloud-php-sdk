# Instance types (`/v2/products/instances`)

Source: [`src/Resource/V2/InstancesResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/InstancesResource.php)

Ergonomic alias for `$client->products->instances()`. Returns the catalogue
of available runtime instance types (node, php, python, docker, …) with
their flavors and variants.

## Access

```php
$client->instances
```

## Methods

```php
public function list(): list<InstanceType>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v2/products/instances` |

To get the flavors and variants for a single type, call `list()` and
filter client-side on `$instanceType->type`:

```php
$nodes = array_filter(
    $client->instances->list(),
    fn ($i) => $i->type === 'node',
);
```

## `InstanceType` DTO

Fields (verified against
[`src/Model/InstanceType.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/InstanceType.php)):

```php
public string  $type;          // 'node', 'php', 'docker', ...
public ?string $version;
public ?string $name;
public ?string $description;
public ?bool   $enabled;
public ?bool   $comingSoon;
public ?int    $maxInstances;
public array   $flavors;       // list<Flavor> available for this type
public array   $variants;      // list<string> runtime variants
```

## History

The 1.0.0 version of `InstancesResource` also exposed `get(type[, version])`,
`flavors(type)`, and `types()` methods, all of which targeted V4 routes
that Clever Cloud doesn't actually expose (every call returned 404).
They were removed in 1.0.1 — see
[`CHANGELOG.md`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/CHANGELOG.md).
