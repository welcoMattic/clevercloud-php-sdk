<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Resource\AbstractV4Resource;
use CleverCloud\Sdk\Streaming\LogStream;

/**
 * Real-time and historical application logs.
 *
 * Uses the V4 endpoint: /v4/logs/organisations/{ownerId}/applications/{applicationId}/logs
 * The endpoint only supports Server-Sent Events, not JSON lists.
 */
final readonly class LogsResource extends AbstractV4Resource
{
    private const int DEFAULT_QUERY_LIMIT = 100;

    /**
     * Opens an SSE stream for live logs. The returned LogStream is iterable -
     * `foreach` over it consumes log entries as they arrive, decoded via
     * Symfony's {@see \Symfony\Component\HttpClient\EventSourceHttpClient}.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string, limit?: int} $filters
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function stream(string $applicationId, ?string $organisationId = null, array $filters = []): LogStream
    {
        $ownerId = $this->resolveOwnerId($organisationId);
        $path = $this->logsPath($ownerId, $applicationId);

        $handle = $this->httpEventStream($path, ['query' => $filters]);

        return new LogStream($handle, $this->mapper);
    }

    /**
     * Returns historical log entries as a one-shot list, by consuming the SSE
     * stream and stopping early.
     *
     * Two bounds are needed, and both matter. `limit` is sent upstream, and the
     * server does close the connection once that many entries have been sent.
     * But it only ever reaches that point on a chatty application: for a quiet
     * one the endpoint holds the connection open and emits HEARTBEAT events
     * forever, and no combination of `since` / `until` / `limit` makes it hang
     * up (verified against the live API). So `$maxDurationSeconds` is what
     * actually guarantees this method returns. Raise it if you are pulling a
     * large window and see truncated results.
     *
     * @param array{since?: string, until?: string, filter?: string, deploymentId?: string, limit?: int} $filters
     * @param int                                                                                        $maxDurationSeconds wall-clock budget for consuming the stream
     *
     * @return list<LogEntry>
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function query(
        string $applicationId,
        ?string $organisationId = null,
        array $filters = [],
        int $maxDurationSeconds = 10,
    ): array {
        $ownerId = $this->resolveOwnerId($organisationId);

        $limit = $filters['limit'] ?? self::DEFAULT_QUERY_LIMIT;
        $filters['limit'] = $limit;

        $handle = $this->httpEventStream(
            $this->logsPath($ownerId, $applicationId),
            ['query' => $filters],
        );
        $stream = new LogStream($handle, $this->mapper, $maxDurationSeconds);

        $entries = [];
        foreach ($stream as $entry) {
            $entries[] = $entry;
            if (\count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Resolves the organisation id to use for the request.
     * When null, fetches the personal organisation id via GET /v2/self.
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    private function resolveOwnerId(?string $organisationId): string
    {
        if (null !== $organisationId) {
            return $organisationId;
        }

        // Fetch personal organisation id via V2 API
        $payload = $this->http->request('GET', ApiVersion::V2, '/self');

        if (!isset($payload['id']) || !\is_string($payload['id']) || '' === $payload['id']) {
            throw new ApiException('Could not resolve personal organisation id from /v2/self response', 0);
        }

        return $payload['id'];
    }

    private function logsPath(string $ownerId, string $applicationId): string
    {
        return '/logs/organisations/'.rawurlencode($ownerId).'/applications/'.rawurlencode($applicationId).'/logs';
    }
}
