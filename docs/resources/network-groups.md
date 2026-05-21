# Network Groups (`/v4/networkgroups/...`)

Source: [`src/Resource/V4/NetworkGroupsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/NetworkGroupsResource.php)

WireGuard-based private networks linking applications, add-ons, and external
peers together.

## Access

```php
$client->networkGroups
```

`?string $organisationId = null` → scopes to `/self`.

## CRUD

```php
public function list(?string $organisationId = null): list<NetworkGroup>
public function get(string $networkGroupId, ?string $organisationId = null): NetworkGroup
public function create(string $label, ?string $description = null, ?string $organisationId = null): NetworkGroup
public function delete(string $networkGroupId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/networkgroups/.../networkgroups` | — |
| `get()` | GET | `/v4/networkgroups/.../networkgroups/{id}` | — |
| `create()` | POST | `/v4/networkgroups/.../networkgroups` | `{"label": "...", "description": "..."?}` |
| `delete()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}` | — |

## Members

```php
public function members(string $networkGroupId, ?string $organisationId = null): list<NetworkGroupMember>
public function addMember(string $networkGroupId, string $memberId, string $kind, ?string $label = null, ?string $organisationId = null): void
public function removeMember(string $networkGroupId, string $memberId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `members()` | GET | `/v4/networkgroups/.../networkgroups/{id}/members` | — |
| `addMember()` | POST | `/v4/networkgroups/.../networkgroups/{id}/members` | `{"id": "...", "kind": "...", "label": "..."?}` |
| `removeMember()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}/members/{memberId}` | — |

`$kind` is the member type (`application`, `addon`, `external` …) per the
Clever Cloud Network Groups documentation.

## External peer WireGuard config

```php
public function externalPeerConfig(string $networkGroupId, string $peerId, ?string $organisationId = null): string
```

`GET /v4/networkgroups/.../networkgroups/{id}/external-peers/{peerId}/config`
— returns the raw WireGuard configuration body as a string. Use the
response to provision an external peer (laptop, on-prem server, …).
