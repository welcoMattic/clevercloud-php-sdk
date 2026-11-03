<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\Enum\WebhookFormat;
use CleverCloud\Sdk\Model\WebhookUrl;
use CleverCloud\Sdk\Resource\V2\WebhooksResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(WebhooksResource::class)]
final class WebhooksResourceTest extends TestCase
{
    public function testListReturnsTypedWebhooks(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'webhook_1',
                'ownerId' => 'orga_1',
                'name' => 'chat',
                'urls' => [['format' => 'slack', 'url' => 'https://hooks.example/1']],
                'events' => ['META_DEPLOYMENT_RESULT'],
                'scope' => ['app_1'],
                'state' => 'ENABLED',
            ],
        ]);

        $webhooks = $this->resource($response)->list('orga_1');

        self::assertCount(1, $webhooks);
        self::assertSame('webhook_1', $webhooks[0]->id);
        self::assertSame('orga_1', $webhooks[0]->ownerId);
        self::assertSame('chat', $webhooks[0]->name);
        self::assertEquals([new WebhookUrl('https://hooks.example/1', WebhookFormat::Slack)], $webhooks[0]->urls);
        self::assertSame(['META_DEPLOYMENT_RESULT'], $webhooks[0]->events);
        self::assertSame(['app_1'], $webhooks[0]->scope);
        self::assertSame('ENABLED', $webhooks[0]->state);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/webhooks/orga_1',
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
            'https://api.clever-cloud.com/v2/notifications/webhooks/user_1',
            $response->getRequestUrl(),
        );
    }

    public function testCreatePostsUrlsList(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'webhook_1', 'name' => 'chat']);

        $webhook = $this->resource($response)->create(
            'chat',
            'https://hooks.example/1',
            ['DEPLOYMENT_FAIL'],
            WebhookFormat::Slack,
            ['app_1'],
            'orga_1',
        );

        self::assertSame('webhook_1', $webhook->id);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/webhooks/orga_1',
            $response->getRequestUrl(),
        );
        self::assertSame(
            '{"name":"chat","urls":[{"format":"slack","url":"https://hooks.example/1"}],"events":["DEPLOYMENT_FAIL"],"scope":["app_1"]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testCreateOmitsEmptyEventsAndScope(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'webhook_1']);

        $this->resource($response)->create('all', 'https://hooks.example/1', [], organisationId: 'orga_1');

        self::assertSame(
            '{"name":"all","urls":[{"format":"raw","url":"https://hooks.example/1"}]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteTargetsWebhook(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('webhook_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/notifications/webhooks/orga_1/webhook_1',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse ...$responses): WebhooksResource
    {
        return new WebhooksResource(
            ResourceFactory::http(new MockHttpClient($responses)),
            ResourceFactory::mapper(),
        );
    }
}
