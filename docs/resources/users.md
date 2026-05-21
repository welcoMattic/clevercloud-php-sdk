# Users (`/v2/users`)

Source: [`src/Resource/V2/UsersResource.php`](../../src/Resource/V2/UsersResource.php)

Read-only inspection of other users (the ones you have access to via shared
organisations).

## Access

```php
$client->users
```

## Methods

```php
public function get(string $userId): User
public function applications(string $userId): list<array<string, mixed>>
public function addons(string $userId): list<array<string, mixed>>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `get()` | GET | `/v2/users/{id}` |
| `applications()` | GET | `/v2/users/{id}/applications` (raw list) |
| `addons()` | GET | `/v2/users/{id}/addons` (raw list) |

For your own self, use [`$client->self`](self.md).
