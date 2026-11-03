<?php

namespace CleverCloud\Sdk\Resource\Root;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Model\Enum\LogExportArchive;
use CleverCloud\Sdk\Model\Enum\LogExportFileFormat;
use CleverCloud\Sdk\Model\LogExport;
use CleverCloud\Sdk\Model\LogExportPage;
use CleverCloud\Sdk\Resource\AbstractResource;
use Psr\Http\Message\ResponseInterface;

/**
 * Asynchronous exports of an organisation's runtime logs and access logs,
 * under the unversioned `/organisations/{orgId}/logs/exports` path.
 *
 * Workflow: {@see self::create()} answers with an export in `PENDING`; poll
 * {@see self::get()} until its status is terminal, then {@see self::download()}
 * the archive once it is `DONE`.
 */
final readonly class LogExportsResource extends AbstractResource
{
    /**
     * Lists the organisation's log exports, newest first, without their items.
     *
     * @param int|null    $limit  max exports per page (API default 100, max 200, silently clamped)
     * @param string|null $cursor the `nextCursor` of the previous page
     */
    public function list(string $organisationId, ?int $limit = null, ?string $cursor = null): LogExportPage
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->basePath($organisationId),
            ['query' => ['limit' => $limit, 'cursor' => $cursor]],
        );

        return $this->mapTo(LogExportPage::class, $payload);
    }

    /**
     * Starts an export over a time window, for the whole organisation or a
     * list of its applications and add-ons. At least one of `$logs` and
     * `$accessLogs` must be true.
     *
     * @param string                   $since       start of the window, RFC 3339
     * @param string|null              $until       end of the window, RFC 3339, after `$since`; null means now
     * @param list<string>|null        $resourceIds 1 to 2000 application ids (`app_…`) or add-on real ids (`postgresql_…`); an `addon_…` id is refused; null exports every resource of the organisation
     * @param LogExportFileFormat|null $fileFormat  null lets the API pick `json`, the only format produced today
     * @param LogExportArchive|null    $archive     null lets the API pick the default for the file format
     */
    public function create(
        string $organisationId,
        string $since,
        ?string $until = null,
        bool $logs = true,
        bool $accessLogs = false,
        ?array $resourceIds = null,
        ?LogExportFileFormat $fileFormat = null,
        ?LogExportArchive $archive = null,
    ): LogExport {
        $body = ['since' => $since, 'logs' => $logs, 'accesslogs' => $accessLogs];
        if (null !== $until) {
            $body['until'] = $until;
        }
        if (null !== $resourceIds) {
            $body['resourceIds'] = $resourceIds;
        }
        if (null !== $fileFormat) {
            $body['fileFormat'] = $fileFormat->value;
        }
        if (null !== $archive) {
            $body['archive'] = $archive->value;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $body]);

        return $this->mapTo(LogExport::class, $payload);
    }

    /**
     * Returns one export, with its progress and the items of its archive.
     */
    public function get(string $organisationId, string $exportId): LogExport
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->exportPath($organisationId, $exportId));

        return $this->mapTo(LogExport::class, $payload);
    }

    /**
     * Cancels an export in flight, or deletes the archive of a finished one.
     * The export stays listed as `CANCELLED` until it expires.
     */
    public function delete(string $organisationId, string $exportId): void
    {
        $this->httpDelete($this->exportPath($organisationId, $exportId));
    }

    /**
     * Downloads the archive of a `DONE` export and returns the raw response,
     * so callers can stream the body to disk.
     *
     * The API answers with a 302 to a short-lived presigned URL. The SDK's
     * Symfony HttpClient follows it and drops the `Authorization` header on
     * that cross-host hop, as the presigned URL requires. The API answers 409
     * while the export is not `DONE` and 410 once its archive has expired.
     */
    public function download(string $organisationId, string $exportId): ResponseInterface
    {
        return $this->httpStream(
            'GET',
            $this->exportPath($organisationId, $exportId).'/download',
            ['headers' => ['Accept' => '*/*']],
        );
    }

    protected function version(): ApiVersion
    {
        return ApiVersion::Root;
    }

    private function basePath(string $organisationId): string
    {
        return '/organisations/'.rawurlencode($organisationId).'/logs/exports';
    }

    private function exportPath(string $organisationId, string $exportId): string
    {
        return $this->basePath($organisationId).'/'.rawurlencode($exportId);
    }
}
