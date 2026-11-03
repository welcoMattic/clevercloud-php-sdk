<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\EmailNotification;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Manages e-mail notifications under `/v2/notifications/emailhooks/{ownerId}`.
 *
 * The route has no `/self` alias: a `null` `$organisationId` resolves the
 * personal organisation id (`user_<uuid>`) through `GET /v2/self` first.
 */
final readonly class NotificationsResource extends AbstractV2Resource
{
    /**
     * Lists the e-mail notifications of an organisation, or of the personal
     * space when `$organisationId` is null.
     *
     * @return list<EmailNotification>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(EmailNotification::class, $payload);
    }

    /**
     * Creates an e-mail notification.
     *
     * Each target is an e-mail address (contains `@`), a user id (`user_`
     * prefix) or the literal `organisation` for every member of the owner.
     *
     * @param list<string> $events  event types, meta events included (empty = all)
     * @param list<string> $targets recipients: e-mail addresses, user ids or `organisation`
     * @param list<string> $scope   app / add-on ids the notification fires for (empty = all)
     */
    public function create(
        string $name,
        array $events,
        array $targets,
        array $scope = [],
        ?string $organisationId = null,
    ): EmailNotification {
        $body = [
            'name' => $name,
            'notified' => array_map(self::target(...), $targets),
        ];
        if ([] !== $events) {
            $body['events'] = $events;
        }
        if ([] !== $scope) {
            $body['scope'] = $scope;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $body]);

        return $this->mapTo(EmailNotification::class, $payload);
    }

    /**
     * Deletes an e-mail notification.
     */
    public function delete(string $notificationId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($organisationId).'/'.rawurlencode($notificationId),
        );
    }

    /**
     * @return array{type: string, target?: string}
     */
    private static function target(string $target): array
    {
        if ('organisation' === strtolower($target)) {
            return ['type' => 'organisation'];
        }

        if (str_starts_with($target, 'user_')) {
            return ['type' => 'userid', 'target' => $target];
        }

        return ['type' => 'email', 'target' => $target];
    }

    private function basePath(?string $organisationId): string
    {
        return '/notifications/emailhooks/'.rawurlencode($this->resolveOwnerId($organisationId));
    }
}
