<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\StreamEvent;
use CleverCloud\Sdk\Resource\V2\EventsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(EventsResource::class)]
final class EventsResourceTest extends TestCase
{
    public function testStreamYieldsPlatformEvents(): void
    {
        $payload = ['appId' => 'app_1', 'ownerId' => 'orga_1'];
        $response = new MockResponse(
            [": keepalive\n\n", "event: DEPLOYMENT_SUCCESS\ndata: ".json_encode($payload, JSON_THROW_ON_ERROR)."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        /** @var list<StreamEvent> $events */
        $events = iterator_to_array($this->resource($response)->stream(), false);

        self::assertCount(1, $events);
        self::assertSame('DEPLOYMENT_SUCCESS', $events[0]->type);
        self::assertSame($payload, $events[0]->data);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/events', $response->getRequestUrl());
        self::assertSame('Accept: text/event-stream', ResourceFactory::findHeader($response, 'Accept'));
    }

    public function testStreamFiltersOnTypes(): void
    {
        $response = new MockResponse([], ['response_headers' => ['content-type' => 'text/event-stream']]);

        iterator_to_array($this->resource($response)->stream(['DEPLOYMENT_SUCCESS', 'APPLICATION_CREATION']), false);

        self::assertSame(
            'https://api.clever-cloud.com/v2/events?types=DEPLOYMENT_SUCCESS%2CAPPLICATION_CREATION',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): EventsResource
    {
        return new EventsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
