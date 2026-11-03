<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\LogEntry;
use CleverCloud\Sdk\Model\StreamEvent;
use CleverCloud\Sdk\Resource\AbstractV4Resource;
use CleverCloud\Sdk\Streaming\EventStream;
use CleverCloud\Sdk\Streaming\LogStream;

/**
 * Real-time and historical runtime logs and access logs.
 *
 * Uses the V4 endpoints:
 * - /v4/logs/organisations/{ownerId}/applications/{applicationId}/logs
 * - /v4/logs/organisations/{ownerId}/resources/{resourceId}/logs
 * - /v4/accesslogs/organisations/{ownerId}/applications/{applicationId}/accesslogs
 * - /v4/accesslogs/organisations/{ownerId}/resources/{resourceId}/accesslogs
 *
 * They only support Server-Sent Events, not JSON lists. Every filter key is
 * sent as a query parameter; list values are sent as a repeated parameter and
 * booleans as `true` / `false`.
 *
 * @phpstan-type ApplicationLogFilters array{
 *     since?: string,
 *     until?: string,
 *     limit?: int,
 *     deploymentId?: string,
 *     instanceId?: string|list<string>,
 *     filter?: string,
 *     service?: string|list<string>,
 *     field?: string|list<string>,
 *     maxRetryDurationInSeconds?: int,
 *     throttleElements?: int,
 *     throttlePerInMilliseconds?: int,
 *     color?: bool,
 * }
 * @phpstan-type ResourceLogFilters array{
 *     since?: string,
 *     until?: string,
 *     limit?: int,
 *     instanceId?: string|list<string>,
 *     filter?: string,
 *     service?: string|list<string>,
 *     search?: string,
 *     tag?: string|list<string>,
 *     severity?: string,
 *     field?: string|list<string>,
 *     maxRetryDurationInSeconds?: int,
 *     throttleElements?: int,
 *     throttlePerInMilliseconds?: int,
 *     color?: bool,
 * }
 * @phpstan-type AccessLogFilters array{
 *     since?: string,
 *     until?: string,
 *     limit?: int,
 *     field?: string|list<string>,
 *     maxRetryDurationInSeconds?: int,
 *     throttleElements?: int,
 *     throttlePerInMilliseconds?: int,
 * }
 */
final readonly class LogsResource extends AbstractV4Resource
{
    private const int DEFAULT_QUERY_LIMIT = 100;

    /**
     * Opens an SSE stream for an application's live runtime logs. The returned
     * LogStream is iterable - `foreach` over it consumes log entries as they
     * arrive, decoded via Symfony's
     * {@see \Symfony\Component\HttpClient\EventSourceHttpClient}.
     *
     * @param ApplicationLogFilters $filters
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function stream(string $applicationId, ?string $organisationId = null, array $filters = []): LogStream
    {
        $path = $this->logsPath($this->resolveOwnerId($organisationId), 'applications', $applicationId);

        return new LogStream($this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]), $this->mapper);
    }

    /**
     * Returns an application's historical log entries as a one-shot list, by
     * consuming the SSE stream and stopping early.
     *
     * Pass a `since` filter. Without one the endpoint sends only entries
     * produced from now on, so this method degenerates into a live tail that
     * returns whatever happened to arrive before the budget expired, which is
     * almost certainly not what you wanted from a "query".
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
     * @param ApplicationLogFilters $filters
     * @param int                   $maxDurationSeconds wall-clock budget for consuming the stream
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
        $filters['limit'] ??= self::DEFAULT_QUERY_LIMIT;
        $path = $this->logsPath($this->resolveOwnerId($organisationId), 'applications', $applicationId);
        $handle = $this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]);

        return self::collect(new LogStream($handle, $this->mapper, $maxDurationSeconds), $filters['limit']);
    }

    /**
     * Opens an SSE stream for the live runtime logs of an add-on or a
     * Kubernetes cluster (`RESOURCE_LOG` events). Add-on services are
     * streamed by default: for an application, prefer {@see self::stream()}
     * or pass `service: 'all'`.
     *
     * `$resourceId` is an application id (`app_…`), an add-on id (`addon_…`),
     * an add-on real id (`postgresql_…`, …) or a Kubernetes cluster id
     * (`kubernetes_…`). The `search`, `tag` and `severity` filters apply to
     * Kubernetes clusters only.
     *
     * @param ResourceLogFilters $filters
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function streamResource(string $resourceId, ?string $organisationId = null, array $filters = []): LogStream
    {
        $path = $this->logsPath($this->resolveOwnerId($organisationId), 'resources', $resourceId);

        return new LogStream($this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]), $this->mapper);
    }

    /**
     * Returns an add-on's or a Kubernetes cluster's historical log entries as
     * a one-shot list. Same bounds and caveats as {@see self::query()}: pass
     * `since`, and rely on `$maxDurationSeconds` to return on a quiet resource.
     *
     * @param ResourceLogFilters $filters
     * @param int                $maxDurationSeconds wall-clock budget for consuming the stream
     *
     * @return list<LogEntry>
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function queryResource(
        string $resourceId,
        ?string $organisationId = null,
        array $filters = [],
        int $maxDurationSeconds = 10,
    ): array {
        $filters['limit'] ??= self::DEFAULT_QUERY_LIMIT;
        $path = $this->logsPath($this->resolveOwnerId($organisationId), 'resources', $resourceId);
        $handle = $this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]);

        return self::collect(new LogStream($handle, $this->mapper, $maxDurationSeconds), $filters['limit']);
    }

    /**
     * Opens an SSE stream for an application's live HTTP access logs
     * (`ACCESS_LOG` events). The API documents no schema for their payload,
     * so each one is yielded as a {@see StreamEvent} holding the decoded JSON.
     *
     * `$applicationId` is an application id (`app_…`) or an add-on real id,
     * used as given: an `addon_…` id is not resolved, use
     * {@see self::streamResourceAccessLogs()} for those.
     *
     * @param AccessLogFilters $filters
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function streamAccessLogs(string $applicationId, ?string $organisationId = null, array $filters = []): EventStream
    {
        $path = $this->accessLogsPath($this->resolveOwnerId($organisationId), 'applications', $applicationId);

        return new EventStream($this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]));
    }

    /**
     * Returns an application's historical access logs as a one-shot list.
     * Same bounds and caveats as {@see self::query()}.
     *
     * @param AccessLogFilters $filters
     * @param int              $maxDurationSeconds wall-clock budget for consuming the stream
     *
     * @return list<StreamEvent>
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function queryAccessLogs(
        string $applicationId,
        ?string $organisationId = null,
        array $filters = [],
        int $maxDurationSeconds = 10,
    ): array {
        $filters['limit'] ??= self::DEFAULT_QUERY_LIMIT;
        $path = $this->accessLogsPath($this->resolveOwnerId($organisationId), 'applications', $applicationId);
        $handle = $this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]);

        return self::collect(new EventStream($handle, $maxDurationSeconds), $filters['limit']);
    }

    /**
     * Opens an SSE stream for the live HTTP access logs of an application or
     * an add-on (`ACCESS_LOG` events), each yielded as a {@see StreamEvent}.
     *
     * `$resourceId` is an application id (`app_…`), an add-on id (`addon_…`)
     * or an add-on real id.
     *
     * @param AccessLogFilters $filters
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function streamResourceAccessLogs(string $resourceId, ?string $organisationId = null, array $filters = []): EventStream
    {
        $path = $this->accessLogsPath($this->resolveOwnerId($organisationId), 'resources', $resourceId);

        return new EventStream($this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]));
    }

    /**
     * Returns the historical access logs of an application or an add-on as a
     * one-shot list. Same bounds and caveats as {@see self::query()}.
     *
     * @param AccessLogFilters $filters
     * @param int              $maxDurationSeconds wall-clock budget for consuming the stream
     *
     * @return list<StreamEvent>
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    public function queryResourceAccessLogs(
        string $resourceId,
        ?string $organisationId = null,
        array $filters = [],
        int $maxDurationSeconds = 10,
    ): array {
        $filters['limit'] ??= self::DEFAULT_QUERY_LIMIT;
        $path = $this->accessLogsPath($this->resolveOwnerId($organisationId), 'resources', $resourceId);
        $handle = $this->httpEventStream($path, ['query' => self::normaliseFilters($filters)]);

        return self::collect(new EventStream($handle, $maxDurationSeconds), $filters['limit']);
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

    /**
     * @param 'applications'|'resources' $kind
     */
    private function logsPath(string $ownerId, string $kind, string $id): string
    {
        return '/logs/organisations/'.rawurlencode($ownerId).'/'.$kind.'/'.rawurlencode($id).'/logs';
    }

    /**
     * @param 'applications'|'resources' $kind
     */
    private function accessLogsPath(string $ownerId, string $kind, string $id): string
    {
        return '/accesslogs/organisations/'.rawurlencode($ownerId).'/'.$kind.'/'.rawurlencode($id).'/accesslogs';
    }

    /**
     * Booleans are sent as `true` / `false`, which is what the API parses,
     * instead of PHP's `1` / empty string.
     *
     * @param array<string, scalar|list<string>> $filters
     *
     * @return array<string, scalar|list<scalar>>
     */
    private static function normaliseFilters(array $filters): array
    {
        return array_map(
            static fn (bool|float|int|string|array $value): float|int|string|array => \is_bool($value) ? ($value ? 'true' : 'false') : $value,
            $filters,
        );
    }

    /**
     * @template T
     *
     * @param iterable<T> $stream
     *
     * @return list<T>
     */
    private static function collect(iterable $stream, int $limit): array
    {
        $items = [];
        foreach ($stream as $item) {
            $items[] = $item;
            if (\count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}
