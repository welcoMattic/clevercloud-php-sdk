<?php

namespace CleverCloud\Sdk\Tests\Unit\Streaming;

use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\AuthException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Exception\ServerException;
use CleverCloud\Sdk\Model\StreamEvent;
use CleverCloud\Sdk\Streaming\EventStream;
use CleverCloud\Sdk\Streaming\SseStreamHandle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(EventStream::class)]
final class EventStreamTest extends TestCase
{
    public function testSkipsHeartbeatsCommentsAndInvalidPayloads(): void
    {
        $stream = self::stream(new MockResponse([
            "event: HEARTBEAT\ndata: {\"ts\":1}\n\n",
            ": keepalive\n\n",
            "data: not json\n\n",
            "data: \"scalar\"\n\n",
            "data: \n\n",
            "data: {\"ok\":true}\n\n",
        ], ['response_headers' => ['content-type' => 'text/event-stream']]));

        /** @var list<StreamEvent> $events */
        $events = iterator_to_array($stream, false);

        self::assertCount(1, $events);
        self::assertSame('message', $events[0]->type);
        self::assertSame(['ok' => true], $events[0]->data);
        self::assertNull($events[0]->id);
    }

    public function testEndOfStreamEndsIteration(): void
    {
        $stream = self::stream(new MockResponse([
            "event: ACCESS_LOG\ndata: {\"n\":1}\n\n",
            "event: END_OF_STREAM\ndata: {\"endedBy\":\"LIMIT_REACHED\"}\n\n",
            "event: ACCESS_LOG\ndata: {\"n\":2}\n\n",
        ], ['response_headers' => ['content-type' => 'text/event-stream']]));

        self::assertCount(1, iterator_to_array($stream, false));
    }

    /**
     * @param class-string<ApiException> $exception
     */
    #[DataProvider('errorStatuses')]
    public function testMapsErrorStatusToTypedException(int $status, string $exception): void
    {
        $stream = self::stream(new MockResponse('{"message":"nope"}', [
            'http_code' => $status,
            'response_headers' => ['content-type' => 'application/json'],
        ]));

        try {
            iterator_to_array($stream, false);
            self::fail('Expected an exception');
        } catch (ApiException $e) {
            self::assertInstanceOf($exception, $e);
            self::assertSame($status, $e->statusCode);
        }
    }

    /**
     * @return iterable<string, array{int, class-string<ApiException>}>
     */
    public static function errorStatuses(): iterable
    {
        yield '401' => [401, AuthException::class];
        yield '403' => [403, AuthException::class];
        yield '404' => [404, NotFoundException::class];
        yield '400' => [400, ApiException::class];
        yield '503' => [503, ServerException::class];
    }

    private static function stream(MockResponse $response): EventStream
    {
        $client = new EventSourceHttpClient(new MockHttpClient([$response]));

        return new EventStream(new SseStreamHandle($client, $client->connect('https://api.clever-cloud.com/v2/events')));
    }
}
