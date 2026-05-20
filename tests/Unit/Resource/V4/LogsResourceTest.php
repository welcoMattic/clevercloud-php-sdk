<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Resource\V4\LogsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(LogsResource::class)]
final class LogsResourceTest extends TestCase
{
    public function testStreamYieldsTypedLogEntries(): void
    {
        $frame1 = json_encode([
            'message' => 'hello',
            'instance_id' => 'i_1',
            'date' => '2026-05-20T10:00:00Z',
        ], JSON_THROW_ON_ERROR);
        $frame2 = json_encode([
            'message' => 'world',
            'instance_id' => 'i_2',
            'date' => '2026-05-20T10:00:01Z',
        ], JSON_THROW_ON_ERROR);

        $response = new MockResponse(
            ['data: '.$frame1."\n\n", 'data: '.$frame2."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->stream('app_42', 'orga_1'),
            false,
        );

        self::assertCount(2, $entries);
        self::assertSame('hello', $entries[0]->message);
        self::assertSame('i_1', $entries[0]->instanceId);
        self::assertSame('world', $entries[1]->message);
        self::assertSame('i_2', $entries[1]->instanceId);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/applications/app_42/logs',
            $response->getRequestUrl(),
        );
    }

    public function testStreamScopedToSelfWhenNoOrganisation(): void
    {
        $frame = json_encode(['message' => 'x'], JSON_THROW_ON_ERROR);
        $response = new MockResponse(
            ['data: '.$frame."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->stream('app_42'),
            false,
        );

        self::assertCount(1, $entries);
        self::assertSame('x', $entries[0]->message);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/self/applications/app_42/logs',
            $response->getRequestUrl(),
        );
    }

    public function testQueryReturnsTypedList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['message' => 'old', 'instance_id' => 'i_0'],
        ]);

        $entries = $this->resource(new MockHttpClient([$response]))
            ->query('app_42', 'orga_1', ['limit' => 50]);

        self::assertCount(1, $entries);
        self::assertSame('old', $entries[0]->message);
        self::assertSame('i_0', $entries[0]->instanceId);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/applications/app_42/logs?limit=50',
            $response->getRequestUrl(),
        );
    }

    public function testQueryRequestsSelfScopeForNoOrg(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $entries = $this->resource(new MockHttpClient([$response]))->query('app_42');

        self::assertSame([], $entries);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/self/applications/app_42/logs',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockHttpClient $mock): LogsResource
    {
        return new LogsResource(ResourceFactory::http($mock), ResourceFactory::mapper());
    }
}
