<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\EmailNotificationTarget;
use CleverCloud\Sdk\Resource\V2\NotificationsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(NotificationsResource::class)]
final class NotificationsResourceTest extends TestCase
{
    public function testListReturnsTypedNotifications(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'email_1',
                'ownerId' => 'orga_1',
                'name' => 'deploys',
                'notified' => [
                    ['type' => 'email', 'target' => 'ops@example.com'],
                    ['type' => 'organisation'],
                ],
                'events' => ['META_DEPLOYMENT_RESULT'],
                'scope' => ['app_1'],
            ],
        ]);

        $notifications = $this->resource($response)->list('orga_1');

        self::assertCount(1, $notifications);
        self::assertSame('email_1', $notifications[0]->id);
        self::assertSame('orga_1', $notifications[0]->ownerId);
        self::assertSame('deploys', $notifications[0]->name);
        self::assertEquals(
            [new EmailNotificationTarget('email', 'ops@example.com'), new EmailNotificationTarget('organisation')],
            $notifications[0]->targets,
        );
        self::assertSame(['META_DEPLOYMENT_RESULT'], $notifications[0]->events);
        self::assertSame(['app_1'], $notifications[0]->scope);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/emailhooks/orga_1',
            $response->getRequestUrl(),
        );
    }

    public function testListResolvesPersonalOrganisationForSelf(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($self, $response)->list();

        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/emailhooks/user_1',
            $response->getRequestUrl(),
        );
    }

    public function testCreatePostsTypedTargets(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'email_1', 'name' => 'deploys']);

        $notification = $this->resource($response)->create(
            'deploys',
            ['DEPLOYMENT_FAIL'],
            ['ops@example.com', 'user_2', 'organisation'],
            ['app_1'],
            'orga_1',
        );

        self::assertSame('email_1', $notification->id);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/emailhooks/orga_1',
            $response->getRequestUrl(),
        );
        self::assertSame(
            '{"name":"deploys","notified":[{"type":"email","target":"ops@example.com"},{"type":"userid","target":"user_2"},{"type":"organisation"}],"events":["DEPLOYMENT_FAIL"],"scope":["app_1"]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testCreateOmitsEmptyEventsAndScope(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'email_1']);

        $this->resource($response)->create('all', [], ['organisation'], [], 'orga_1');

        self::assertSame(
            '{"name":"all","notified":[{"type":"organisation"}]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteTargetsNotification(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('email_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/emailhooks/orga_1/email_1',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse ...$responses): NotificationsResource
    {
        return new NotificationsResource(
            ResourceFactory::http(new MockHttpClient($responses)),
            ResourceFactory::mapper(),
        );
    }
}
