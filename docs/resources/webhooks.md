# Webhooks (`/v2/notifications/webhooks/...`)

Source: [`src/Resource/V2/WebhooksResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/WebhooksResource.php)

## Access

```php
$client->webhooks
```

The route takes the owner id and has no `/self` alias: with a `null`
`$organisationId` the SDK first reads the personal organisation id
(`user_xxx`) from `GET /v2/self`, so each call costs two requests.

## Methods

```php
public function list(?string $organisationId = null): list<Webhook>
public function create(
    string $name,
    string $url,
    array $events, // list<string> (empty = all)
    WebhookFormat $format = WebhookFormat::Raw, // Raw / Slack / Gitter / Flowdock
    array $scope = [], // list<string>: app/add-on ids (empty = all)
    ?string $organisationId = null,
): Webhook
public function delete(string $webhookId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v2/notifications/webhooks/{ownerId}` | - |
| `create()` | POST | `/v2/notifications/webhooks/{ownerId}` | `{"name", "urls": [{"format", "url"}], "events", "scope"}` |
| `delete()` | DELETE | `/v2/notifications/webhooks/{ownerId}/{id}` | - |

Empty `$events` or `$scope` are left out of the body.

## `WebhookFormat` enum

`CleverCloud\Sdk\Model\Enum\WebhookFormat`. Cases:

- `Raw`: `raw`
- `Slack`: `slack`
- `Gitter`: `gitter`
- `Flowdock`: `flowdock`

## `Webhook` DTO

See [`src/Model/Webhook.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Webhook.php).
Fields: `id`, `ownerId`, `name`, `urls` (`list<WebhookUrl>` with `url` and
`format`), `events`, `scope`, `state`.
