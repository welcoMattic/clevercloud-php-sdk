# Self (`/v2/self`)

Source: [`src/Resource/V2/SelfResource.php`](../../src/Resource/V2/SelfResource.php)

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
public function regenerateMfaBackupCodes(): list<string>
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `startMfa()` | POST | `/v2/self/mfa/{kind}` | `{"kind": "<kind>"}` |
| `confirmMfa()` | POST | `/v2/self/mfa/{kind}/confirmation` | `{"code": "<code>"}` |
| `disableMfa()` | DELETE | `/v2/self/mfa/{kind}` | — |
| `regenerateMfaBackupCodes()` | POST | `/v2/self/mfa/backupcodes` | — |

`startMfa()` returns a raw array typically containing `{kind, otpUrl?, qrCodeUrl?, backupCodes?}`.

## Password

```php
public function changePassword(string $oldPassword, string $newPassword): void
```

`PUT /v2/self/change_password`, body `{"oldPassword": "...", "newPassword": "..."}`.
