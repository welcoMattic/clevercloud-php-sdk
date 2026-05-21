<?php

namespace CleverCloud\Sdk\Streaming;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\AuthException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Exception\ServerException;
use CleverCloud\Sdk\Exception\TransportException;
use CleverCloud\Sdk\Model\LogEntry;
use Generator;
use IteratorAggregate;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\Exception\EventSourceException;
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
            $first = true;
            foreach ($this->handle->client->stream($this->handle->response) as $chunk) {
                // Surface a non-2xx response as a typed SDK exception rather
                // than silently ending the iteration. EventSourceHttpClient
                // does not throw on its own when the upstream returns 4xx/5xx.
                if ($first) {
                    $first = false;
                    $status = $this->handle->response->getStatusCode();
                    if ($status < 200 || $status >= 300) {
                        throw match (true) {
                            401 === $status, 403 === $status => new AuthException(\sprintf('SSE log stream rejected: HTTP %d', $status), $status),
                            404 === $status => new NotFoundException(\sprintf('SSE log stream endpoint not found: HTTP %d', $status), $status),
                            $status >= 500 => new ServerException(\sprintf('SSE log stream upstream error: HTTP %d', $status), $status),
                            default => new ApiException(\sprintf('SSE log stream returned HTTP %d', $status), $status),
                        };
                    }
                }

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
        } catch (EventSourceException $e) {
            // Symfony's EventSourceHttpClient throws this when the upstream
            // does NOT return `text/event-stream` (typically a 4xx/5xx HTML or
            // JSON error). Re-emit a typed SDK exception with the status code
            // we can read off the response.
            $status = $this->handle->response->getStatusCode();
            throw match (true) {
                401 === $status, 403 === $status => new AuthException($e->getMessage(), $status, previous: $e),
                404 === $status => new NotFoundException($e->getMessage(), $status, previous: $e),
                $status >= 500 => new ServerException($e->getMessage(), $status, previous: $e),
                default => new ApiException($e->getMessage(), $status, previous: $e),
            };
        } catch (SfHttpExceptionInterface $e) {
            // Network-layer failure (DNS, TLS, connection reset, timeout).
            throw new TransportException('SSE log stream transport error: '.$e->getMessage(), 0, $e);
        }
    }
}
