# API tokens (`api-bridge.clever-cloud.com`)

Source: [`src/Resource/Bridge/ApiTokensResource.php`](../../src/Resource/Bridge/ApiTokensResource.php)

CRUD over Personal API tokens. Lives on the **api-bridge** gateway, not the
regular API host.

## Access

```php
$client->apiTokens
```

## Authentication requirement

This resource only works when your client is authenticated with a Bearer
token — `api-bridge.clever-cloud.com` returns 401 for OAuth1 callers. The
demo dashboard handles this gracefully via `oauth_blocked.html.twig`; the
SDK does **not** check upfront — the API will respond 401 if you call it
with the wrong credentials.

## Methods

```php
public function list(): list<ApiToken>
public function get(string $tokenId): ApiToken
public function create(array $payload): ApiToken
public function update(string $tokenId, array $payload): ApiToken
public function delete(string $tokenId): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `https://api-bridge.clever-cloud.com/v2/api-tokens` | — |
| `get()` | GET | `.../v2/api-tokens/{id}` | — |
| `create()` | POST | `.../v2/api-tokens` | `{"name": "...", "scopes": ["..."]?, "expires_at": "..."?}` |
| `update()` | PATCH | `.../v2/api-tokens/{id}` | `{"name"?: "...", "scopes"?: [...]}` |
| `delete()` | DELETE | `.../v2/api-tokens/{id}` | — |

The SDK builds these URLs by:
1. Using `ApiVersion::Bridge` (which `AbstractBridgeResource::version()` pins).
2. `Configuration::baseUrlFor(Bridge)` returning `bridgeBaseUrl` (default
   `https://api-bridge.clever-cloud.com`).
3. The resource itself writing the full `/v2/api-tokens` path — there's no
   implicit `/v1` or `/v2` prefix on the Bridge base URL.

## `ApiToken` DTO

Fields (verified against
[`src/Model/ApiToken.php`](../../src/Model/ApiToken.php)):

```php
public string  $id;
public string  $name;
public ?string $token;        // ⚠ Only populated on creation responses — store it immediately
public array   $scopes;       // list<string>
public ?string $createdAt;
public ?string $expiresAt;
public ?string $lastUsedAt;
public ?string $ipAddress;
```

## Creating a token

```php
$token = $client->apiTokens->create([
    'name'       => 'CI deploys',
    'scopes'     => ['application:read', 'application:write'],
    'expires_at' => '2027-01-01T00:00:00Z', // optional
]);

// IMPORTANT: $token->token is the plaintext value. It's only returned ONCE.
// Subsequent list() / get() calls will leave it null.
echo "Save this token: ".$token->token;
```

## See also

- The [`api-token.php` example](../../examples/api-token.php) shows the full
  flow.
- [Authentication guide](../authentication.md) for the Bearer routing
  semantics.
