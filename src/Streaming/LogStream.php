<?php

namespace CleverCloud\Sdk\Streaming;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Model\LogEntry;
use Generator;
use IteratorAggregate;

/**
 * Decodes a Server-Sent Events stream of Clever Cloud log entries into typed
 * {@see LogEntry} objects.
 *
 * Framing, error mapping, heartbeat skipping and the `END_OF_STREAM` event are
 * handled by {@see EventStream}; this class only maps each event payload onto
 * a {@see LogEntry}.
 *
 * @implements IteratorAggregate<int, LogEntry>
 */
final readonly class LogStream implements IteratorAggregate
{
    /**
     * @param int|null $maxDurationSeconds stop iterating after this many seconds, even if the
     *                                     upstream keeps the connection open. Null (the default)
     *                                     tails forever, which is what live streaming wants.
     *                                     Bounded consumers need it: the Clever Cloud endpoint
     *                                     never closes an idle stream, it just emits HEARTBEAT
     *                                     events indefinitely.
     */
    public function __construct(
        private SseStreamHandle $handle,
        private AutoMapperInterface $mapper,
        private ?int $maxDurationSeconds = null,
    ) {
    }

    /**
     * @return Generator<int, LogEntry, mixed, void>
     */
    public function getIterator(): Generator
    {
        foreach (new EventStream($this->handle, $this->maxDurationSeconds) as $event) {
            $entry = $this->mapper->map($event->data, LogEntry::class);
            if ($entry instanceof LogEntry) {
                yield $entry;
            }
        }
    }
}
