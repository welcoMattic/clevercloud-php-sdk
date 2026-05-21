# TCP redirections (`/v2/.../applications/{id}/tcp-redirs`)

Source: [`src/Resource/V2/TcpRedirectionsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/TcpRedirectionsResource.php)

Expose a raw TCP socket on a Clever Cloud-managed port — useful for
non-HTTP protocols (PostgreSQL replication, custom binary protocols, …).

## Access

```php
$client->tcpRedirections
```

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null): list<TcpRedirection>
public function namespaces(?string $organisationId = null): list<string>
public function add(string $applicationId, string $namespace, ?string $organisationId = null): TcpRedirection
public function remove(string $applicationId, int $port, string $namespace, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v2/.../applications/{id}/tcp-redirs` | — |
| `namespaces()` | GET | `/v2/.../tcp-redirs/namespaces` | — |
| `add()` | POST | `/v2/.../applications/{id}/tcp-redirs` | `{"namespace": "..."}` |
| `remove()` | DELETE | `/v2/.../applications/{id}/tcp-redirs/{port}?namespace=...` | — |

`add()` doesn't take a port — the API picks one and returns it on the
resulting `TcpRedirection`. `remove()` requires both the port number and
the namespace (the SDK builds the URL with the namespace as a query
parameter, verified against the source).

## `TcpRedirection` DTO

Fields (verified against
[`src/Model/TcpRedirection.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/TcpRedirection.php)):

```php
public int    $port;
public string $namespace;
```
