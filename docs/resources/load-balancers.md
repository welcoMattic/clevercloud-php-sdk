# Load balancers (`/v4/load-balancers/...`)

Source: [`src/Resource/V4/LoadBalancersResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/LoadBalancersResource.php)

## Access

```php
$client->loadBalancers
```

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null): list<LoadBalancer>
public function get(string $applicationId, string $loadBalancerId, ?string $organisationId = null): LoadBalancer
public function dnsInfo(string $applicationId, ?string $organisationId = null): array<string, mixed>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v4/load-balancers/.../applications/{appId}/load-balancers` |
| `get()` | GET | `/v4/load-balancers/.../applications/{appId}/load-balancers/{lbId}` |
| `dnsInfo()` | GET | `/v4/load-balancers/.../applications/{appId}/load-balancers/default/dns` |

`dnsInfo()` returns the DNS records (A / AAAA / CNAME) that should point at
the load balancer for a given application — useful when configuring a
custom domain.

## `LoadBalancer` DTO

See [`src/Model/LoadBalancer.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/LoadBalancer.php).
