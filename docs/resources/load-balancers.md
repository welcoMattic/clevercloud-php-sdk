# Load balancers (`/v4/load-balancers/...`)

Source: [`src/Resource/V4/LoadBalancersResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/LoadBalancersResource.php)

## Access

```php
$client->loadBalancers
```

Paths are scoped to `/organisations/{id}`. A null `?string $organisationId`
targets your personal space, whose `user_...` id is read from `GET /v2/self`
first (V4 has no `/self` variant).

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null, string $kind = 'default'): list<LoadBalancer>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v4/load-balancers/organisations/{id}/applications/{appId}/load-balancers/{kind}` |

`$kind` is `default` (the public load balancers) or `private`. The API
returns one load balancer per zone, each with the DNS records to point a
custom domain at it: `dns['cname']` for a CNAME record, `dns['a']` (IPv4
addresses) for A records.

```php
foreach ($client->loadBalancers->list('app_...', 'orga_...') as $lb) {
    echo $lb->name, ' ', $lb->dns['cname'] ?? '', PHP_EOL;
}
```

## `LoadBalancer` DTO

`id`, `name` (zone name, e.g. `par`), `zoneId`, `dns`. See
[`src/Model/LoadBalancer.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/LoadBalancer.php).
