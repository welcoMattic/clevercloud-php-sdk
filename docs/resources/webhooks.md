# Webhooks (`/v4/notifications/webhooks/...`)

Source: [`src/Resource/V4/WebhooksResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/WebhooksResource.php)

## Access

```php
$client->webhooks
```

## Methods

```php
public function list(?string $organisationId = null): list<Webhook>
public function create(
    string $name,
    string $url,
    array $events,                                  // list<string>
    WebhookFormat $format = WebhookFormat::Raw,     // Raw / Slack / Gitter / Flowdock
    array $scope = [],                              // list<string> app/addon IDs
    ?string $organisationId = null,
): Webhook
public function delete(string $webhookId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/notifications/webhooks/{owner}` | — |
| `create()` | POST | `/v4/notifications/webhooks/{owner}` | `{"name", "url", "format", "events", "scope"}` |
| `delete()` | DELETE | `/v4/notifications/webhooks/{owner}/{id}` | — |

## `WebhookFormat` enum

`CleverCloud\Sdk\Model\Enum\WebhookFormat`. Cases:

- `Raw` — `raw`
- `Slack` — `slack`
- `Gitter` — `gitter`
- `Flowdock` — `flowdock`

## `Webhook` DTO

See [`src/Model/Webhook.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Webhook.php).
