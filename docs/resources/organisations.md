# Organisations (`/v2/organisations`)

Source: [`src/Resource/V2/OrganisationsResource.php`](../../src/Resource/V2/OrganisationsResource.php)

## Access

```php
$client->organisations
```

## CRUD

| Method | HTTP | Path |
| --- | --- | --- |
| `list(): list<Organisation>` | GET | `/v2/organisations` |
| `get(string $id): Organisation` | GET | `/v2/organisations/{id}` |
| `create(array $data): Organisation` | POST | `/v2/organisations` |
| `update(string $id, array $data): Organisation` | PUT | `/v2/organisations/{id}` |
| `delete(string $id): void` | DELETE | `/v2/organisations/{id}` |

## Members

```php
public function members(string $organisationId): list<Member>
public function addMember(string $organisationId, string $userEmail, MemberRole $role, ?string $job = null): void
public function updateMember(string $organisationId, string $userId, MemberRole $role, ?string $job = null): void
public function removeMember(string $organisationId, string $userId): void
```

`MemberRole` is the enum at `CleverCloud\Sdk\Model\Enum\MemberRole`
(`Admin`, `Manager`, `Developer`, `Accounting`).

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `members()` | GET | `/v2/organisations/{id}/members` | — |
| `addMember()` | POST | `/v2/organisations/{id}/members` | `{"email": "...", "role": "ADMIN", "job": "..."?}` |
| `updateMember()` | PUT | `/v2/organisations/{id}/members/{userId}` | `{"role": "...", "job": "..."?}` |
| `removeMember()` | DELETE | `/v2/organisations/{id}/members/{userId}` | — |

## OAuth consumers (per-organisation)

Same CRUD as user-level consumers but scoped to the organisation:

```php
public function consumers(string $organisationId): list<OAuthConsumer>
public function getConsumer(string $organisationId, string $consumerKey): OAuthConsumer
public function createConsumer(string $organisationId, array $data): OAuthConsumer
public function updateConsumer(string $organisationId, string $consumerKey, array $data): OAuthConsumer
public function deleteConsumer(string $organisationId, string $consumerKey): void
```

Routes: `/v2/organisations/{id}/consumers[/{consumerKey}]`. Payload shape
same as [Self consumers](self.md#oauth-consumers-your-registered-apps).

## Namespaces (TCP / network routing)

```php
public function namespaces(string $organisationId): list<Namespace_>
```

`GET /v2/organisations/{id}/namespaces`. Returns the namespaces the
organisation is authorised to bind TCP ports on (see
[TCP redirections](tcp-redirections.md)).
