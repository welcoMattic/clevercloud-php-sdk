# Email notifications (`/v2/notifications/emailhooks/...`)

Source: [`src/Resource/V2/NotificationsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/NotificationsResource.php)

## Access

```php
$client->notifications
```

The route takes the owner id and has no `/self` alias: with a `null`
`$organisationId` the SDK first reads the personal organisation id
(`user_xxx`) from `GET /v2/self`, so each call costs two requests.

## Methods

```php
public function list(?string $organisationId = null): list<EmailNotification>
public function create(
    string $name,
    array $events, // list<string>, e.g. ['DEPLOYMENT_FAIL', 'META_CREDITS'] (empty = all)
    array $targets, // list<string>: e-mail addresses, user ids or 'organisation'
    array $scope = [], // list<string>: app/add-on ids (empty = all)
    ?string $organisationId = null,
): EmailNotification
public function delete(string $notificationId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v2/notifications/emailhooks/{ownerId}` | - |
| `create()` | POST | `/v2/notifications/emailhooks/{ownerId}` | `{"name", "notified", "events", "scope"}` |
| `delete()` | DELETE | `/v2/notifications/emailhooks/{ownerId}/{id}` | - |

The API names the recipients `notified`, each one typed. The SDK maps every
`$targets` entry: an address containing `@` becomes
`{"type": "email", "target": "..."}`, a `user_` id becomes
`{"type": "userid", "target": "..."}` and `organisation` becomes
`{"type": "organisation"}` (every member of the owner). Empty `$events` or
`$scope` are left out of the body.

## `EmailNotification` DTO

See [`src/Model/EmailNotification.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/EmailNotification.php).
Fields: `id`, `ownerId`, `name`, `events`, `scope`, `targets`
(`list<EmailNotificationTarget>` with `type` and `target`).
