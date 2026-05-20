<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Enum\WebhookFormat;
use CleverCloud\Sdk\Model\Webhook;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages outgoing webhooks under
 * `/v4/notifications/webhooks/{ownerId}` (use `null` for the current user).
 */
final readonly class WebhooksResource extends AbstractV4Resource
{
    /**
     * @return list<Webhook>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(Webhook::class, $payload);
    }

    /**
     * @param list<string> $events
     * @param list<string> $scope  app / add-on ids the webhook fires for (empty = all)
     */
    public function create(
        string $name,
        string $url,
        array $events,
        WebhookFormat $format = WebhookFormat::Raw,
        array $scope = [],
        ?string $organisationId = null,
    ): Webhook {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), [
            'json' => [
                'name' => $name,
                'url' => $url,
                'format' => $format->value,
                'events' => $events,
                'scope' => $scope,
            ],
        ]);

        return $this->mapTo(Webhook::class, $payload);
    }

    public function delete(string $webhookId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($organisationId).'/'.rawurlencode($webhookId),
        );
    }

    private function basePath(?string $organisationId): string
    {
        $owner = $organisationId ?? 'self';

        return '/notifications/webhooks/'.rawurlencode($owner);
    }
}
