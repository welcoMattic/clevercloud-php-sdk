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

    private function resource(MockHttpClient $mock): LogsResource
    {
        return new LogsResource(ResourceFactory::http($mock), ResourceFactory::mapper());
    }
}
