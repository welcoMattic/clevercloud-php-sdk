<?php

namespace CleverCloud\Sdk\Streaming;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Model\LogEntry;
use Generator;
use IteratorAggregate;

use const JSON_THROW_ON_ERROR;

use JsonException;

/**
 * Decodes a Server-Sent Events stream of Clever Cloud log entries into typed
 * {@see LogEntry} objects.
 *
 * @implements IteratorAggregate<int, LogEntry>
 */
final readonly class LogStream implements IteratorAggregate
{
    public function __construct(
        private SseStream $sse,
        private AutoMapperInterface $mapper,
    ) {
    }

    public function getIterator(): Generator
    {
        foreach ($this->sse as $event) {
            if ('' === $event->data) {
                continue;
            }
            try {
                $decoded = json_decode($event->data, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }
            if (!\is_array($decoded)) {
                continue;
            }
            /** @var array<string, mixed> $decoded */
            $entry = $this->mapper->map($decoded, LogEntry::class);
            if ($entry instanceof LogEntry) {
                yield $entry;
            }
        }
    }
}
