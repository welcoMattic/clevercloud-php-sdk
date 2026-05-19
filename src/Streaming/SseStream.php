<?php

namespace CleverCloud\Sdk\Streaming;

use Generator;
use IteratorAggregate;
use Psr\Http\Message\StreamInterface;

/**
 * Parses an event-stream body into a sequence of {@see SseEvent}s.
 *
 * Follows the WHATWG SSE algorithm closely: reads the body in chunks, splits
 * on LF, accumulates `data:` / `event:` / `id:` / `retry:` fields, and emits
 * an event on a blank-line boundary. Multi-line `data:` is joined with `\n`.
 *
 * @implements IteratorAggregate<int, SseEvent>
 */
final readonly class SseStream implements IteratorAggregate
{
    /**
     * The number of bytes to pull from the underlying PSR-7 stream per read.
     * 8KB is the sweet spot for log-like traffic.
     */
    private const int CHUNK_SIZE = 8_192;

    public function __construct(private StreamInterface $body)
    {
    }

    /**
     * @return Generator<int, SseEvent>
     */
    public function getIterator(): Generator
    {
        $buffer = '';
        $dataLines = [];
        $event = null;
        $id = null;
        $retry = null;

        while (!$this->body->eof()) {
            $chunk = $this->body->read(self::CHUNK_SIZE);
            if ('' === $chunk) {
                continue;
            }
            $buffer .= $chunk;

            while (false !== ($newline = strpos($buffer, "\n"))) {
                $line = substr($buffer, 0, $newline);
                $buffer = substr($buffer, $newline + 1);

                // CRLF support: strip a trailing CR that was part of the line.
                if (str_ends_with($line, "\r")) {
                    $line = substr($line, 0, -1);
                }

                if ('' === $line) {
                    if ([] !== $dataLines || null !== $event || null !== $id) {
                        yield new SseEvent(implode("\n", $dataLines), $event, $id, $retry);
                    }
                    $dataLines = [];
                    $event = null;
                    $id = null;
                    $retry = null;
                    continue;
                }

                if (str_starts_with($line, ':')) {
                    continue;
                }

                [$field, $value] = self::splitField($line);
                $value = ltrim($value, ' ');

                switch ($field) {
                    case 'data':
                        $dataLines[] = $value;
                        break;
                    case 'event':
                        $event = $value;
                        break;
                    case 'id':
                        $id = $value;
                        break;
                    case 'retry':
                        if (ctype_digit($value)) {
                            $retry = (int) $value;
                        }
                        break;
                }
            }
        }

        if ([] !== $dataLines || null !== $event || null !== $id) {
            yield new SseEvent(implode("\n", $dataLines), $event, $id, $retry);
        }
    }

    /**
     * @return array{string, string}
     */
    private static function splitField(string $line): array
    {
        $colon = strpos($line, ':');
        if (false === $colon) {
            return [$line, ''];
        }

        return [substr($line, 0, $colon), substr($line, $colon + 1)];
    }
}
