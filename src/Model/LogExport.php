<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;
use CleverCloud\Sdk\Model\Enum\LogExportArchive;
use CleverCloud\Sdk\Model\Enum\LogExportFileFormat;
use CleverCloud\Sdk\Model\Enum\LogExportStatus;

/**
 * An asynchronous export of an organisation's runtime logs and/or access
 * logs, as returned by `/organisations/{orgId}/logs/exports[/{exportId}]`.
 *
 * Dates are RFC 3339 strings.
 */
final readonly class LogExport
{
    /**
     * @param list<string>|null   $resourceIds the resources asked, sorted and without duplicates; null when the whole organisation was
     * @param int|null            $archiveSize size of the archive in bytes once `DONE`, compressed as it is downloaded
     * @param string|null         $error       why the export failed, in one sentence
     * @param string|null         $expiresAt   when the archive is deleted from storage and the export turns `EXPIRED`
     * @param list<LogExportItem> $items       one per (resource, kind) on a fetch; always empty in a listing
     */
    public function __construct(
        public string $id,
        public LogExportStatus $status,
        public ?string $ownerId = null,
        public ?string $requestedBy = null,
        public bool $logs = false,
        #[MapFrom(property: 'accesslogs')]
        public bool $accessLogs = false,
        public ?array $resourceIds = null,
        public ?LogExportFileFormat $fileFormat = null,
        public ?LogExportArchive $archive = null,
        public ?string $since = null,
        public ?string $until = null,
        public ?int $archiveSize = null,
        public ?string $fileName = null,
        public ?string $error = null,
        public ?string $createdAt = null,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
        public ?string $expiresAt = null,
        public ?LogExportProgress $progress = null,
        public array $items = [],
    ) {
    }
}
