# Self (`/v2/self`)

Source: [`src/Resource/V2/SelfResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/SelfResource.php)

Read and mutate the currently-authenticated user.

## Access

```php
$client->self
```

## Profile

```php
public function get(): User
public function update(array $data): User
```

| Method | HTTP | Path | Notes |
| --- | --- | --- | --- |
| `get()` | GET | `/v2/self` | |
| `update(array $data)` | PUT | `/v2/self` | Payload keys: `firstname`, `lastname`, `address`, etc. |

Note: the read shape exposes only a single `name` field, not separate `firstname`/`lastname`.
The accepted write keys are not verified.

## SSH keys

```php
public function sshKeys(): list<SshKey>
public function addSshKey(string $name, string $publicKey): void
public function removeSshKey(string $name): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `sshKeys()` | GET | `/v2/self/keys` | — |
| `addSshKey()` | PUT | `/v2/self/keys/{name}` | `{"key": "<publicKey>"}` |
| `removeSshKey()` | DELETE | `/v2/self/keys/{name}` | — |

## Secondary email addresses

```php
public function emailAddresses(): list<EmailAddress>
public function addEmailAddress(string $email, bool $makePrimary = false): void
public function removeEmailAddress(string $email): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `emailAddresses()` | GET | `/v2/self/emails` | — |
| `addEmailAddress()` | PUT | `/v2/self/emails/{email}` | `{"make_primary": <bool>}` |
| `removeEmailAddress()` | DELETE | `/v2/self/emails/{email}` | — |

## OAuth consumers (your registered apps)

```php
public function consumers(): list<OAuthConsumer>
public function getConsumer(string $consumerKey): OAuthConsumer
public function createConsumer(array $data): OAuthConsumer
public function updateConsumer(string $consumerKey, array $data): OAuthConsumer
public function deleteConsumer(string $consumerKey): void
```

`create()` payload shape (from the source):

```php
[
    'name' => 'My OAuth consumer',     // required
    'url'  => 'https://example.org',   // required
    'baseUrl'     => '...',            // optional
    'description' => '...',            // optional
    'picture'     => '...',            // optional URL
    'rights'      => ['...'],          // optional list of scope strings
]
```

## MFA (2FA)

```php
public function startMfa(string $kind = 'TOTP'): array<string, mixed>
public function confirmMfa(string $kind, string $code): void
public function disableMfa(string $kind): void
public function regenerateMfaBackupCodes(string $kind = 'TOTP'): list<string>
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `startMfa()` | POST | `/v2/self/mfa/{kind}` | `{"kind": "<kind>"}` |
| `confirmMfa()` | POST | `/v2/self/mfa/{kind}/confirmation` | `{"code": "<code>"}` |
| `disableMfa()` | DELETE | `/v2/self/mfa/{kind}` | — |
| `regenerateMfaBackupCodes()` | POST | `/v2/self/mfa/{kind}/backupcodes` | - |

`startMfa()` returns a raw array typically containing `{kind, otpUrl?, qrCodeUrl?, backupCodes?}`.

`regenerateMfaBackupCodes()` unwraps the `backupCodes` list of the response.
The previous codes stop working, and this response is the only one that shows
the new ones. The API requires an almighty token and the account password
(base64) in an `X-Clever-Password` header, which this method does not send
yet.

## Password

```php
public function changePassword(string $oldPassword, string $newPassword): void
```

`PUT /v2/self/change_password`, body `{"oldPassword": "...", "newPassword": "..."}`.

## Account

```php
public function id(): string
public function setAvatar(AvatarSource $source): string
public function resendConfirmationEmail(): void
public function validateEmail(string $validationKey): void
public function organisations(): list<Organisation>
public function instances(): array<string, list<array<string, mixed>>>
public function paymentInfo(): PaymentInfo
```

| Method | HTTP | Path | Notes |
| --- | --- | --- | --- |
| `id()` | GET | `/v2/self/id` | Returns the `ownerId` (`user_...`) |
| `setAvatar()` | PUT | `/v2/self/avatar` | Body `{"source": "gravatar"\|"github"}`, returns the avatar URL |
| `resendConfirmationEmail()` | GET | `/v2/self/confirmation_email` | |
| `validateEmail()` | GET | `/v2/self/validate_email?validationKey={key}` | |
| `organisations()` | GET | `/v2/self/organisations` | |
| `instances()` | GET | `/v2/self/instances` | Raw instances keyed by application id |
| `paymentInfo()` | GET | `/v2/self/payment-info` | `PaymentInfo` model: billing details plus `errors` keyed by field |

`AvatarSource` is the enum at `CleverCloud\Sdk\Model\Enum\AvatarSource`
(`Gravatar`, `Github`). The API derives the avatar URL from the source; a
URL cannot be supplied.

## OAuth access tokens

```php
public function tokens(): list<OAuthAccessToken>
public function currentToken(): OAuthAccessToken
public function revokeToken(string $token): void
public function revokeAllTokens(): void
public function cliTokens(string $cliToken): string|array<string, mixed>
public function consumerSecret(string $consumerKey): string
public function consumerTokens(string $consumerKey): list<OAuthAccessToken>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `tokens()` | GET | `/v2/self/tokens` |
| `currentToken()` | GET | `/v2/self/tokens/current` |
| `revokeToken()` | DELETE | `/v2/self/tokens/{token}` |
| `revokeAllTokens()` | DELETE | `/v2/self/tokens` |
| `cliTokens()` | GET | `/v2/self/cli_tokens?cli_token={cliToken}` |
| `consumerSecret()` | GET | `/v2/self/consumers/{key}/secret` |
| `consumerTokens()` | GET | `/v2/self/consumers/{key}/tokens` |

`OAuthAccessToken` exposes `token`, `consumer` (raw array), `rights` (map of
right name to boolean) and the `creationDate`, `expirationDate` and
`lastUtilisation` timestamps (ms since epoch).

`revokeAllTokens()` also revokes the token that signs the request.

`cliTokens()` collects the tokens of a pending command-line login and does
not require authentication. The spec declares a JSON string response while
the Clever Cloud CLI reads a `{token, secret}` object, so the decoded body
is returned as is.

## MFA status and preference

```php
public function mfaStatus(string $kind = 'TOTP'): MfaStatus
public function setFavouriteMfa(string $kind): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `mfaStatus()` | GET | `/v2/self/mfa/{kind}` |
| `setFavouriteMfa()` | PUT | `/v2/self/mfa/{kind}` |

`MfaStatus` exposes `kind`, `configured` and `backupCodesState` (raw
`{remaining, total, generatedAt}` array, null when not configured).
