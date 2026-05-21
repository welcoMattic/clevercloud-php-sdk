# Domains / vhosts (`/v2/.../applications/{id}/vhosts`)

Source: [`src/Resource/V2/DomainsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/DomainsResource.php)

## Access

```php
$client->domains
```

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null): list<Vhost>
public function get(string $applicationId, string $fqdn, ?string $organisationId = null): Vhost
public function add(string $applicationId, string $fqdn, ?string $organisationId = null): void
public function remove(string $applicationId, string $fqdn, ?string $organisationId = null): void
public function favourite(string $applicationId, ?string $organisationId = null): ?Vhost
public function setFavourite(string $applicationId, string $fqdn, ?string $organisationId = null): void
public function unsetFavourite(string $applicationId, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v2/.../applications/{id}/vhosts` |
| `get()` | GET | `/v2/.../applications/{id}/vhosts/{fqdn}` |
| `add()` | POST | `/v2/.../applications/{id}/vhosts/{fqdn}` (no body) |
| `remove()` | DELETE | `/v2/.../applications/{id}/vhosts/{fqdn}` |
| `favourite()` | GET | `/v2/.../applications/{id}/vhosts/favourite` (returns `null` if no favourite set) |
| `setFavourite()` | PUT | `/v2/.../applications/{id}/vhosts/favourite` (body: `{"fqdn": "..."}`) |
| `unsetFavourite()` | DELETE | `/v2/.../applications/{id}/vhosts/favourite` |

## `Vhost` DTO

Single field `public string $fqdn` (see
[`src/Model/Vhost.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Vhost.php)).

The "favourite" vhost (also referred to as "primary domain" in the
Console) is the one Clever Cloud's HTTP responses advertise as canonical.
