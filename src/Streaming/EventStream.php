<?php

namespace CleverCloud\Sdk\Streaming;

use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\AuthException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Exception\ServerException;
use CleverCloud\Sdk\Exception\TransportException;
use CleverCloud\Sdk\Model\StreamEvent;
use Generator;
use IteratorAggregate;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\Exception\EventSourceException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as SfHttpExceptionInterface;
use Throwable;

/**
 * Decodes a Clever Cloud Server-Sent Events stream into {@see StreamEvent}
 * objects carrying the JSON-decoded `data` of each event.
 *
 * Built on top of Symfony's {@see ServerSentEvent} chunks: the underlying
 * Symfony HttpClient handles framing, reconnection, and last-event-id tracking.
 *
 * - `HEARTBEAT` events, keep-alive comments and events with an empty or
 *   non-JSON `data` are skipped.
 * - An `END_OF_STREAM` event (sent when `limit`, `until` or the server-side
 *   timeout is reached) ends the iteration.
 *
 * @implements IteratorAggregate<int, StreamEvent>
 */
final readonly class EventStream implements IteratorAggregate
{
    public const string HEARTBEAT = 'HEARTBEAT';
    public const string END_OF_STREAM = 'END_OF_STREAM';

    /**
     * @param int|null $maxDurationSeconds stop iterating after this many seconds, even if the
     *                                     upstream keeps the connection open. Null (the default)
     *                                     tails forever, which is what live streaming wants.
     */
    public function __construct(
        private SseStreamHandle $handle,
        private ?int $maxDurationSeconds = null,
    ) {
    }

    /**
     * @return Generator<int, StreamEvent, mixed, void>
     */
    public function getIterator(): Generator
    {
        $deadline = null !== $this->maxDurationSeconds
            ? microtime(true) + $this->maxDurationSeconds
            : null;

        try {
            $first = true;
            foreach ($this->handle->client->stream($this->handle->response) as $chunk) {
                // Checked on every chunk, not just on decoded events, so that a
                // stream carrying nothing but heartbeats still honours the budget.
                if (null !== $deadline && microtime(true) >= $deadline) {
                    return;
                }

                // Surface a non-2xx response as a typed SDK exception rather
                // than silently ending the iteration. EventSourceHttpClient
                // does not throw on its own when the upstream returns 4xx/5xx.
                if ($first) {
                    $first = false;
                    $status = $this->handle->response->getStatusCode();
                    if ($status < 200 || $status >= 300) {
                        throw self::exceptionFor($status, \sprintf('SSE stream returned HTTP %d', $status));
                    }
                }

                if (!$chunk instanceof ServerSentEvent) {
                    continue;
                }

                $type = $chunk->getType();
                if (self::END_OF_STREAM === $type) {
                    return;
                }
                if (self::HEARTBEAT === $type) {
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
                yield new StreamEvent($type, $decoded, '' !== $chunk->getId() ? $chunk->getId() : null);
            }
        } catch (EventSourceException $e) {
            // Symfony's EventSourceHttpClient throws this when the upstream
            // does NOT return `text/event-stream` (typically a 4xx/5xx HTML or
            // JSON error). Re-emit a typed SDK exception with the status code
            // we can read off the response.
            throw self::exceptionFor($this->handle->response->getStatusCode(), $e->getMessage(), $e);
        } catch (SfHttpExceptionInterface $e) {
            // Network-layer failure (DNS, TLS, connection reset, timeout).
            throw new TransportException('SSE stream transport error: '.$e->getMessage(), 0, $e);
        }
    }

    private static function exceptionFor(int $status, string $message, ?Throwable $previous = null): ApiException
    {
        return match (true) {
            401 === $status, 403 === $status => new AuthException($message, $status, previous: $previous),
            404 === $status => new NotFoundException($message, $status, previous: $previous),
            $status >= 500 => new ServerException($message, $status, previous: $previous),
            default => new ApiException($message, $status, previous: $previous),
        };
    }
}
