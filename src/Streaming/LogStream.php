<?php

namespace CleverCloud\Sdk\Streaming;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Model\LogEntry;
use Generator;
use IteratorAggregate;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as SfHttpExceptionInterface;

/**
 * Decodes a Server-Sent Events stream of Clever Cloud log entries into typed
 * {@see LogEntry} objects.
 *
 * Built on top of Symfony's {@see ServerSentEvent} chunks — the underlying
 * Symfony HttpClient handles framing, reconnection, and last-event-id tracking.
 *
 * @implements IteratorAggregate<int, LogEntry>
 */
final readonly class LogStream implements IteratorAggregate
{
    public function __construct(
        private SseStreamHandle $handle,
        private AutoMapperInterface $mapper,
    ) {
    }

    public function getIterator(): Generator
    {
        try {
            foreach ($this->handle->client->stream($this->handle->response) as $chunk) {
                if (!$chunk instanceof ServerSentEvent) {
                    continue;
                }

                $data = $chunk->getData();
                if ('' === $data) {
                    continue;
                }

                try {
                    $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
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
        } catch (SfHttpExceptionInterface) {
            // Stream closed or errored — propagate as end-of-iteration.
        }
    }
}
