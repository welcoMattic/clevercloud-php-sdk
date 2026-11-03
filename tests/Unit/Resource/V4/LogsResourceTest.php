<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Model\StreamEvent;
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
    public function testStreamYieldsTypedLogEntriesWithCamelCase(): void
    {
        $frame1 = json_encode([
            'message' => 'hello',
            'instanceId' => '0f244f66-e210-4238-873b-d51c7459e454',
            'date' => '2026-05-20T10:00:00Z',
            'id' => '271242095:0:0',
            'severity' => 'info',
            'priority' => 6,
            'applicationId' => 'app_7fbf',
            'deploymentId' => 'deployment_4c3671f4',
            'commitId' => 'f9cca975',
            'service' => 'run-p2993-i2993',
            'region' => 'unknown',
            'zone' => 'par',
            'version' => '1',
        ], JSON_THROW_ON_ERROR);
        $frame2 = json_encode([
            'message' => 'world',
            'instanceId' => 'i_2',
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
        self::assertSame('0f244f66-e210-4238-873b-d51c7459e454', $entries[0]->instanceId);
        self::assertSame('app_7fbf', $entries[0]->applicationId);
        self::assertSame('world', $entries[1]->message);
        self::assertSame('i_2', $entries[1]->instanceId);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/applications/app_42/logs',
            $response->getRequestUrl(),
        );
    }

    public function testStreamResolvesPersonalOrganisationWhenNoneProvided(): void
    {
        // First response: GET /v2/self returns user id
        $selfResponse = new MockResponse(
            json_encode(['id' => 'user_abc'], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        );

        // Second response: SSE stream
        $frame = json_encode(['message' => 'test log'], JSON_THROW_ON_ERROR);
        $sseResponse = new MockResponse(
            ['data: '.$frame."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array(
            $this->resource(new MockHttpClient([$selfResponse, $sseResponse]))->stream('app_42'),
            false,
        );

        self::assertCount(1, $entries);
        self::assertSame('test log', $entries[0]->message);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/user_abc/applications/app_42/logs',
            $sseResponse->getRequestUrl(),
        );
    }

    public function testQueryReturnsTypedListWithSse(): void
    {
        $frame = json_encode([
            'message' => 'old log',
            'instanceId' => 'i_0',
            'date' => '2026-05-20T10:00:00Z',
        ], JSON_THROW_ON_ERROR);

        $response = new MockResponse(
            ['data: '.$frame."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $entries = $this->resource(new MockHttpClient([$response]))
            ->query('app_42', 'orga_1', ['limit' => 50]);

        self::assertIsArray($entries);
        self::assertCount(1, $entries);
        self::assertSame('old log', $entries[0]->message);
        self::assertSame('i_0', $entries[0]->instanceId);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/applications/app_42/logs?limit=50',
            $response->getRequestUrl(),
        );
    }

    public function testQueryUsesDefaultLimitOf100WhenNotProvided(): void
    {
        $frame = json_encode([
            'message' => 'log 1',
            'instanceId' => 'i_1',
        ], JSON_THROW_ON_ERROR);
        $frame2 = json_encode([
            'message' => 'log 2',
            'instanceId' => 'i_2',
        ], JSON_THROW_ON_ERROR);

        // Generate 100 frames
        $frames = [];
        for ($i = 0; $i < 100; ++$i) {
            $frameData = json_encode([
                'message' => 'log '.$i,
                'instanceId' => 'i_'.$i,
            ], JSON_THROW_ON_ERROR);
            $frames[] = 'data: '.$frameData."\n\n";
        }

        $response = new MockResponse(
            $frames,
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $entries = $this->resource(new MockHttpClient([$response]))
            ->query('app_42', 'orga_1');

        self::assertCount(100, $entries);
        self::assertStringContainsString('limit=100', $response->getRequestUrl());
    }

    public function testQueryResolvesPersonalOrganisationWhenNoneProvided(): void
    {
        // First response: GET /v2/self returns user id
        $selfResponse = new MockResponse(
            json_encode(['id' => 'user_xyz'], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        );

        // Second response: SSE stream with one log entry
        $frame = json_encode(['message' => 'personal org log'], JSON_THROW_ON_ERROR);
        $sseResponse = new MockResponse(
            ['data: '.$frame."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $entries = $this->resource(new MockHttpClient([$selfResponse, $sseResponse]))
            ->query('app_42');

        self::assertCount(1, $entries);
        self::assertSame('personal org log', $entries[0]->message);
        self::assertStringContainsString('organisations/user_xyz', $sseResponse->getRequestUrl());
        self::assertStringContainsString('limit=100', $sseResponse->getRequestUrl());
    }

    /**
     * Regression guard: a stream that only ever emits HEARTBEAT must not trap
     * `query()` in an endless loop. The live endpoint does exactly this on a
     * quiet application, so the wall-clock budget is the only thing that makes
     * the method return.
     */
    public function testQueryStopsOnDurationBudgetWhenOnlyHeartbeatsArrive(): void
    {
        $heartbeats = array_fill(0, 200, "data: \n\n");

        $response = new MockResponse(
            $heartbeats,
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $startedAt = microtime(true);
        $entries = $this->resource(new MockHttpClient([$response]))
            ->query('app_42', 'orga_1', [], maxDurationSeconds: 0);
        $elapsed = microtime(true) - $startedAt;

        self::assertSame([], $entries);
        self::assertLessThan(5, $elapsed, 'query() must give up instead of tailing heartbeats forever.');
    }

    public function testHeartbeatFrameIsSkipped(): void
    {
        // HEARTBEAT frames have empty data
        $heartbeatFrame = "data: \n\n";
        $logFrame = json_encode(['message' => 'real log', 'instanceId' => 'i_1'], JSON_THROW_ON_ERROR);

        $response = new MockResponse(
            [$heartbeatFrame, 'data: '.$logFrame."\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->stream('app_42', 'orga_1'),
            false,
        );

        // Only the real log entry should be yielded, HEARTBEAT is skipped
        self::assertCount(1, $entries);
        self::assertSame('real log', $entries[0]->message);
        self::assertSame('i_1', $entries[0]->instanceId);
    }

    public function testStreamSendsEverySpecFilter(): void
    {
        $response = self::sse([]);

        iterator_to_array($this->resource(new MockHttpClient([$response]))->stream('app_42', 'orga_1', [
            'since' => '2026-11-01T00:00:00Z',
            'instanceId' => ['i_1', 'i_2'],
            'service' => 'all',
            'field' => ['message', 'date'],
            'maxRetryDurationInSeconds' => 60,
            'throttleElements' => 10,
            'throttlePerInMilliseconds' => 500,
            'color' => false,
        ]), false);

        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/applications/app_42/logs'
            .'?since=2026-11-01T00%3A00%3A00Z&instanceId=i_1&instanceId=i_2&service=all&field=message&field=date'
            .'&maxRetryDurationInSeconds=60&throttleElements=10&throttlePerInMilliseconds=500&color=false',
            $response->getRequestUrl(),
        );
    }

    public function testStreamStopsAtEndOfStream(): void
    {
        $response = self::sse([
            "event: APPLICATION_LOG\ndata: ".json_encode(['message' => 'first'], JSON_THROW_ON_ERROR)."\n\n",
            "event: END_OF_STREAM\ndata: ".json_encode(['endedBy' => 'LIMIT_REACHED'], JSON_THROW_ON_ERROR)."\n\n",
            "event: APPLICATION_LOG\ndata: ".json_encode(['message' => 'after end'], JSON_THROW_ON_ERROR)."\n\n",
        ]);

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array($this->resource(new MockHttpClient([$response]))->stream('app_42', 'orga_1'), false);

        self::assertCount(1, $entries);
        self::assertSame('first', $entries[0]->message);
    }

    public function testStreamResourceHitsResourcesPath(): void
    {
        $response = self::sse([
            "event: RESOURCE_LOG\ndata: ".json_encode(['message' => 'pg ready', 'service' => 'postgresql'], JSON_THROW_ON_ERROR)."\n\n",
        ]);

        /** @var list<LogEntry> $entries */
        $entries = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->streamResource('kubernetes_1', 'orga_1', [
                'search' => 'etcd leader',
                'tag' => ['component:etcd', 'pod'],
                'severity' => 'warning',
                'color' => true,
            ]),
            false,
        );

        self::assertCount(1, $entries);
        self::assertSame('pg ready', $entries[0]->message);
        self::assertSame('postgresql', $entries[0]->service);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/resources/kubernetes_1/logs'
            .'?search=etcd%20leader&tag=component%3Aetcd&tag=pod&severity=warning&color=true',
            $response->getRequestUrl(),
        );
    }

    public function testQueryResourceAppliesDefaultLimit(): void
    {
        $response = self::sse(['data: '.json_encode(['message' => 'addon log'], JSON_THROW_ON_ERROR)."\n\n"]);

        $entries = $this->resource(new MockHttpClient([$response]))
            ->queryResource('addon_1', 'orga_1', ['since' => '2026-11-01T00:00:00Z']);

        self::assertCount(1, $entries);
        self::assertSame('addon log', $entries[0]->message);
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/orga_1/resources/addon_1/logs?since=2026-11-01T00%3A00%3A00Z&limit=100',
            $response->getRequestUrl(),
        );
    }

    public function testStreamResourceResolvesPersonalOrganisation(): void
    {
        $selfResponse = ResourceFactory::jsonResponse(200, ['id' => 'user_abc']);
        $sseResponse = self::sse([]);

        iterator_to_array($this->resource(new MockHttpClient([$selfResponse, $sseResponse]))->streamResource('addon_1'), false);

        self::assertSame('https://api.clever-cloud.com/v2/self', $selfResponse->getRequestUrl());
        self::assertSame(
            'https://api.clever-cloud.com/v4/logs/organisations/user_abc/resources/addon_1/logs',
            $sseResponse->getRequestUrl(),
        );
    }

    public function testStreamAccessLogsYieldsRawEvents(): void
    {
        $payload = [
            'date' => '2026-11-01T10:00:00Z',
            'http' => ['request' => ['method' => 'GET', 'path' => '/'], 'response' => ['statusCode' => 200]],
        ];
        $response = self::sse([
            "event: HEARTBEAT\ndata: {}\n\n",
            "id: 42\nevent: ACCESS_LOG\ndata: ".json_encode($payload, JSON_THROW_ON_ERROR)."\n\n",
            ": keepalive\n\n",
        ]);

        /** @var list<StreamEvent> $events */
        $events = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->streamAccessLogs('app_42', 'orga_1', [
                'since' => '2026-11-01T00:00:00Z',
                'field' => ['date', 'http'],
            ]),
            false,
        );

        self::assertCount(1, $events);
        self::assertSame('ACCESS_LOG', $events[0]->type);
        self::assertSame('42', $events[0]->id);
        self::assertSame($payload, $events[0]->data);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/accesslogs/organisations/orga_1/applications/app_42/accesslogs'
            .'?since=2026-11-01T00%3A00%3A00Z&field=date&field=http',
            $response->getRequestUrl(),
        );
    }

    public function testQueryAccessLogsStopsAtLimit(): void
    {
        $frames = [];
        for ($i = 0; $i < 5; ++$i) {
            $frames[] = "event: ACCESS_LOG\ndata: ".json_encode(['n' => $i], JSON_THROW_ON_ERROR)."\n\n";
        }
        $response = self::sse($frames);

        $events = $this->resource(new MockHttpClient([$response]))
            ->queryAccessLogs('app_42', 'orga_1', ['limit' => 2, 'throttleElements' => 50]);

        self::assertCount(2, $events);
        self::assertSame(['n' => 1], $events[1]->data);
        self::assertSame(
            'https://api.clever-cloud.com/v4/accesslogs/organisations/orga_1/applications/app_42/accesslogs?limit=2&throttleElements=50',
            $response->getRequestUrl(),
        );
    }

    public function testStreamResourceAccessLogsHitsResourcesPath(): void
    {
        $response = self::sse(["event: ACCESS_LOG\ndata: ".json_encode(['source' => ['ip' => '192.0.2.1']], JSON_THROW_ON_ERROR)."\n\n"]);

        /** @var list<StreamEvent> $events */
        $events = iterator_to_array(
            $this->resource(new MockHttpClient([$response]))->streamResourceAccessLogs('addon_1', 'orga_1', ['until' => '2026-11-02T00:00:00Z']),
            false,
        );

        self::assertCount(1, $events);
        self::assertSame(['source' => ['ip' => '192.0.2.1']], $events[0]->data);
        self::assertSame(
            'https://api.clever-cloud.com/v4/accesslogs/organisations/orga_1/resources/addon_1/accesslogs?until=2026-11-02T00%3A00%3A00Z',
            $response->getRequestUrl(),
        );
    }

    public function testQueryResourceAccessLogsAppliesDefaultLimit(): void
    {
        $response = self::sse(["event: ACCESS_LOG\ndata: ".json_encode(['n' => 1], JSON_THROW_ON_ERROR)."\n\n"]);

        $events = $this->resource(new MockHttpClient([$response]))
            ->queryResourceAccessLogs('postgresql_1', 'orga_1');

        self::assertCount(1, $events);
        self::assertSame(
            'https://api.clever-cloud.com/v4/accesslogs/organisations/orga_1/resources/postgresql_1/accesslogs?limit=100',
            $response->getRequestUrl(),
        );
    }

    /**
     * @param list<string> $frames
     */
    private static function sse(array $frames): MockResponse
    {
        return new MockResponse($frames, ['response_headers' => ['content-type' => 'text/event-stream']]);
    }

    private function resource(MockHttpClient $mock): LogsResource
    {
        return new LogsResource(ResourceFactory::http($mock), ResourceFactory::mapper());
    }
}
