# Email notifications (`/v4/notifications/emailhooks/...`)

Source: [`src/Resource/V4/NotificationsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/NotificationsResource.php)

## Access

```php
$client->notifications
```

`?string $organisationId = null` → routes to `/v4/notifications/emailhooks/self`.

## Methods

```php
public function list(?string $organisationId = null): list<EmailNotification>
public function create(
    string $name,
    array $events,         // list<string> — e.g. ['*'] or specific event codes
    array $targets,        // list<string> — recipient emails or user IDs
    array $scope = [],     // list<string> — app/addon IDs (empty = all)
    ?string $organisationId = null,
): EmailNotification
public function delete(string $notificationId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/notifications/emailhooks/{owner}` | — |
| `create()` | POST | `/v4/notifications/emailhooks/{owner}` | `{"name", "events", "notified", "scope"}` |
| `delete()` | DELETE | `/v4/notifications/emailhooks/{owner}/{id}` | — |

Note the API field name is `notified` for recipients; the SDK takes
`$targets` and renames the field internally.

## `EmailNotification` DTO

See [`src/Model/EmailNotification.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/EmailNotification.php).
