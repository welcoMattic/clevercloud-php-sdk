<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\EmailNotification;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages email notifications under
 * `/v4/notifications/emailhooks/{ownerId}` (use `null` for the current user).
 */
final readonly class NotificationsResource extends AbstractV4Resource
{
    /**
     * @return list<EmailNotification>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(EmailNotification::class, $payload);
    }

    /**
     * @param list<string> $events
     * @param list<string> $targets recipients
     * @param list<string> $scope   app / add-on ids the notification fires for (empty = all)
     */
    public function create(
        string $name,
        array $events,
        array $targets,
        array $scope = [],
        ?string $organisationId = null,
    ): EmailNotification {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), [
            'json' => [
                'name' => $name,
                'events' => $events,
                'notified' => $targets,
                'scope' => $scope,
            ],
        ]);

        return $this->mapTo(EmailNotification::class, $payload);
    }

    public function delete(string $notificationId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($organisationId).'/'.rawurlencode($notificationId),
        );
    }

    private function basePath(?string $organisationId): string
    {
        $owner = $organisationId ?? 'self';

        return '/notifications/emailhooks/'.rawurlencode($owner);
    }
}
