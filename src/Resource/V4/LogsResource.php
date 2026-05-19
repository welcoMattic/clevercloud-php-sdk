<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Resource\AbstractV4Resource;
use CleverCloud\Sdk\Streaming\LogStream;
use CleverCloud\Sdk\Streaming\SseStream;

/**
 * Real-time and historical application logs against `/v4/logs`.
 */
final readonly class LogsResource extends AbstractV4Resource
{
    /**
     * Opens an SSE stream for live logs. The returned LogStream is iterable —
     * `foreach` over it consumes log entries as they arrive.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string} $filters
     */
    public function stream(string $applicationId, array $filters = []): LogStream
    {
        $response = $this->httpStream(
            'GET',
            '/logs/'.rawurlencode($applicationId),
            [
                'query' => $filters,
                'headers' => ['Accept' => 'text/event-stream'],
            ],
        );

        return new LogStream(new SseStream($response->getBody()), $this->mapper);
    }

    /**
     * Returns historical log entries as a one-shot list. Use {@see stream()}
     * for live tailing.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string, limit?: int} $filters
     *
     * @return list<LogEntry>
     */
    public function query(string $applicationId, array $filters = []): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            '/logs/'.rawurlencode($applicationId),
            ['query' => $filters],
        );

        return $this->mapCollection(LogEntry::class, $payload);
    }
}
