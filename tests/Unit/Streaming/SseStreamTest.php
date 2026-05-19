<?php

namespace CleverCloud\Sdk\Tests\Unit\Streaming;

use CleverCloud\Sdk\Streaming\SseEvent;
use CleverCloud\Sdk\Streaming\SseStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use const SEEK_SET;

#[CoversClass(SseStream::class)]
final class SseStreamTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testYieldsSingleDataEvent(): void
    {
        $events = $this->collect("data: hello\n\n");

        self::assertCount(1, $events);
        self::assertSame('hello', $events[0]->data);
        self::assertNull($events[0]->event);
    }

    public function testJoinsMultipleDataLinesWithNewlines(): void
    {
        $events = $this->collect("data: line one\ndata: line two\n\n");

        self::assertCount(1, $events);
        self::assertSame("line one\nline two", $events[0]->data);
    }

    public function testParsesEventTypeAndId(): void
    {
        $events = $this->collect("event: log\nid: 42\ndata: hello\n\n");

        self::assertCount(1, $events);
        self::assertSame('hello', $events[0]->data);
        self::assertSame('log', $events[0]->event);
        self::assertSame('42', $events[0]->id);
    }

    public function testIgnoresCommentLines(): void
    {
        $events = $this->collect(": this is a comment\ndata: real\n\n");

        self::assertCount(1, $events);
        self::assertSame('real', $events[0]->data);
    }

    public function testParsesRetryAsIntegerMs(): void
    {
        $events = $this->collect("retry: 3500\ndata: hi\n\n");

        self::assertCount(1, $events);
        self::assertSame(3500, $events[0]->retry);
    }

    public function testHandlesCrlfLineEndings(): void
    {
        $events = $this->collect("data: hello\r\n\r\n");

        self::assertCount(1, $events);
        self::assertSame('hello', $events[0]->data);
    }

    public function testEmitsMultipleEventsSeparatedByBlankLines(): void
    {
        $events = $this->collect("data: first\n\ndata: second\n\ndata: third\n\n");

        self::assertCount(3, $events);
        self::assertSame('first', $events[0]->data);
        self::assertSame('second', $events[1]->data);
        self::assertSame('third', $events[2]->data);
    }

    public function testReassemblesAcrossChunkBoundaries(): void
    {
        $chunks = ['data: hel', "lo\n", 'data: world', "\n\n"];
        $stream = new SseStream($this->chunkedStream($chunks));

        $events = iterator_to_array($stream, false);

        self::assertCount(1, $events);
        self::assertSame("hello\nworld", $events[0]->data);
    }

    public function testEmitsFinalEventWithoutTrailingBlankLine(): void
    {
        $events = $this->collect("data: tail\n");

        self::assertCount(1, $events);
        self::assertSame('tail', $events[0]->data);
    }

    /**
     * @return list<SseEvent>
     */
    private function collect(string $body): array
    {
        $stream = new SseStream($this->factory->createStream($body));

        return iterator_to_array($stream, false);
    }

    /**
     * @param list<string> $chunks
     */
    private function chunkedStream(array $chunks): StreamInterface
    {
        return new ChunkedStream($chunks);
    }
}

/**
 * A read-only PSR-7 stream that returns its body in pre-defined chunks so
 * tests can prove the SSE parser handles split-frame inputs correctly.
 */
final class ChunkedStream implements StreamInterface
{
    /** @var list<string> */
    private array $chunks;

    /**
     * @param list<string> $chunks
     */
    public function __construct(array $chunks)
    {
        $this->chunks = $chunks;
    }

    public function __toString(): string
    {
        return implode('', $this->chunks);
    }

    public function close(): void
    {
        $this->chunks = [];
    }

    public function detach()
    {
        $this->chunks = [];

        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return [] === $this->chunks;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('ChunkedStream is not seekable');
    }

    public function rewind(): void
    {
        throw new RuntimeException('ChunkedStream is not seekable');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('ChunkedStream is read-only');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        if ([] === $this->chunks) {
            return '';
        }

        return array_shift($this->chunks);
    }

    public function getContents(): string
    {
        $remaining = implode('', $this->chunks);
        $this->chunks = [];

        return $remaining;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return null === $key ? [] : null;
    }
}
