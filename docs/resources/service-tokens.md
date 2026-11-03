# Service tokens (`/v2/organisations/{id}/service-tokens`)

Source: [`src/Resource/V2/ServiceTokensResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/ServiceTokensResource.php)

Organisation service tokens are [biscuit](https://www.biscuitsec.org/) tokens
that call the API on the organisation's behalf with a given role, optionally
restricted to some applications and add-ons. Once minted, use the value with
`Credentials::serviceToken()`, see the
[Authentication guide](../authentication.md#service-token-biscuit).

## Access

```php
$client->serviceTokens
```

## Authentication requirement

The API declares these four routes as **OAuth 1.0a only**: a client
authenticated with a service token cannot list, mint or revoke service tokens.
Manage them with an OAuth 1.0a client, then hand the minted value to the
workload that needs it.

The caller must be a member of the organisation with the right to manage its
services, and be allowed to assign the requested role. A member without the
`ADMIN` or `MANAGER` role only sees (and reads) the tokens they created; a
member without the `ADMIN` role only revokes the tokens they created.

## Methods

```php
public function list(string $organisationId, ?int $limit = null, ?int $offset = null): list<ServiceToken>
public function listPage(string $organisationId, ?int $limit = null, ?string $cursor = null): ServiceTokenPage
public function create(string $organisationId, string $name, ServiceTokenRole $role, ?string $description = null, ?array $resources = null, ?int $ttlSeconds = null): CreatedServiceToken
public function get(string $organisationId, string $tokenId): ServiceToken
public function delete(string $organisationId, string $tokenId): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v2/organisations/{id}/service-tokens?limit=&offset=` | - |
| `listPage()` | GET | `/v2/organisations/{id}/service-tokens?paginate=1&limit=&cursor=` | - |
| `create()` | POST | `/v2/organisations/{id}/service-tokens` | `{"name": "...", "role": "DEVELOPER", "description": "..."?, "resources": ["app_..."]?, "ttl_seconds": 86400?}` |
| `get()` | GET | `/v2/organisations/{id}/service-tokens/{tokenId}` | - |
| `delete()` | DELETE | `/v2/organisations/{id}/service-tokens/{tokenId}` | - |

Null arguments are left out of the query string and of the request body.
`get()` and `delete()` accept either the token ID or its revocation ID.

`ServiceTokenRole` is the enum at `CleverCloud\Sdk\Model\Enum\ServiceTokenRole`
(`Admin`, `Manager`, `Developer`, `Accounting`).

### Listing

`list()` returns every token at once, newest first; `$offset` skips entries
and `$limit` keeps at most that many. The API refuses this form (HTTP 502)
when the organisation holds more than 10 000 tokens.

`listPage()` sends `paginate=1` and returns one `ServiceTokenPage` (50 tokens
by default, 200 at most). Pass its `nextCursor` back until it is `null`:

```php
$cursor = null;
do {
    $page = $client->serviceTokens->listPage('orga_xxx', limit: 200, cursor: $cursor);
    foreach ($page->items as $token) {
        echo $token->name, ' ', $token->status->value, "\n";
    }
    $cursor = $page->nextCursor;
} while (null !== $cursor);
```

## Creating a token

```php
use CleverCloud\Sdk\Model\Enum\ServiceTokenRole;

$created = $client->serviceTokens->create(
    'orga_xxx',
    name: 'CI deploys',
    role: ServiceTokenRole::Developer,
    description: 'GitHub Actions deploy job',
    resources: ['app_xxx'], // optional: omit (or pass []) for the whole organisation
    ttlSeconds: 30 * 86400, // optional: 90 days by default, one year at most
);

// IMPORTANT: $created->token is the token value. It is returned ONCE,
// list() / get() never return it.
echo 'Save this token: '.$created->token;
echo 'Revocation ID: '.$created->metadata->revocationId;
```

Creation is rate-limited per organisation. A service token cannot be
refreshed: `$created->refreshToken` is always `null`. Mint a new one before
it expires.

## Models

`ServiceToken` (verified against
[`src/Model/ServiceToken.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/ServiceToken.php)):

```php
public string $id;
public string $tenantId; // the organisation as `tenant_...` (`orga_` prefix swapped)
public string $revocationId; // accepted in place of $id by get() / delete()
public ServiceTokenStatus $status; // Active, Expired, Revoked
public string $createdAt; // RFC 3339
public ?string $name;
public ?string $description;
public ?string $tokenType; // SELF_SUSTAINED_ACCESS for tokens minted by create()
public ?string $productId;
public ?string $locationId;
public ?string $resourceId;
public array $tags; // list<string>
public ?string $instigatorId;
public ?string $expiredAt; // RFC 3339
public ?string $revokedAt; // RFC 3339, null while not revoked
public ?string $creatorUserId; // `user_...` of the creating member, on list() / get() only
```

The API does not echo back the role nor the resource restriction: both live
inside the token itself.

`CreatedServiceToken`, returned by `create()`:

```php
public ServiceToken $metadata;
public string $token; // the biscuit value, shown once
public ?string $refreshToken; // always null
```

`ServiceTokenPage`, returned by `listPage()`:

```php
public array $items; // list<ServiceToken>
public ?string $nextCursor; // null on the last page
```

## See also

- [Authentication guide](../authentication.md#service-token-biscuit) for
  where service tokens are accepted.
- [API tokens](api-tokens.md), the personal Bearer tokens routed through
  the api-bridge gateway.
