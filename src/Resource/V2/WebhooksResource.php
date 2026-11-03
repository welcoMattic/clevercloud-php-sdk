<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Enum\WebhookFormat;
use CleverCloud\Sdk\Model\Webhook;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Manages outgoing webhooks under `/v2/notifications/webhooks/{ownerId}`.
 *
 * The route has no `/self` alias: a `null` `$organisationId` resolves the
 * personal organisation id (`user_<uuid>`) through `GET /v2/self` first.
 */
final readonly class WebhooksResource extends AbstractV2Resource
{
    /**
     * Lists the webhooks of an organisation, or of the personal space when
     * `$organisationId` is null.
     *
     * @return list<Webhook>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(Webhook::class, $payload);
    }

    /**
     * Creates a webhook calling `$url` with a body in `$format`.
     *
     * @param list<string> $events event types, meta events included (empty = all)
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
        $body = [
            'name' => $name,
            'urls' => [['format' => $format->value, 'url' => $url]],
        ];
        if ([] !== $events) {
            $body['events'] = $events;
        }
        if ([] !== $scope) {
            $body['scope'] = $scope;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $body]);

        return $this->mapTo(Webhook::class, $payload);
    }

    /**
     * Deletes a webhook.
     */
    public function delete(string $webhookId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($organisationId).'/'.rawurlencode($webhookId),
        );
    }

    private function basePath(?string $organisationId): string
    {
        return '/notifications/webhooks/'.rawurlencode($this->resolveOwnerId($organisationId));
    }
}
