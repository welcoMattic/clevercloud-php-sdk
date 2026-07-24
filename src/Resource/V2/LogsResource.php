<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Resource\AbstractV2Resource;
use CleverCloud\Sdk\Streaming\LogStream;

/**
 * Real-time and historical application logs.
 *
 * Path layout per the documentation:
 * `/v2/organisations/{ownerId}/applications/{applicationId}/logs`
 * or `/v2/self/applications/{applicationId}/logs` when `$organisationId === null`.
 */
final readonly class LogsResource extends AbstractV2Resource
{
    /**
     * Opens an SSE stream for live logs. The returned LogStream is iterable —
     * `foreach` over it consumes log entries as they arrive, decoded via
     * Symfony's {@see \Symfony\Component\HttpClient\EventSourceHttpClient}.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string} $filters
     */
    public function stream(string $applicationId, ?string $organisationId = null, array $filters = []): LogStream
    {
        $handle = $this->httpEventStream(
            $this->logsPath($applicationId, $organisationId),
            ['query' => $filters],
        );

        return new LogStream($handle, $this->mapper);
    }

    /**
     * Returns historical log entries as a one-shot list. Use {@see stream()}
     * for live tailing.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string, limit?: int} $filters
     *
     * @return list<LogEntry>
     */
    public function query(string $applicationId, ?string $organisationId = null, array $filters = []): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->logsPath($applicationId, $organisationId),
            ['query' => $filters],
        );

        return $this->mapCollection(LogEntry::class, $payload);
    }

    private function logsPath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId)
            .'/applications/'.rawurlencode($applicationId).'/logs';
    }
}
