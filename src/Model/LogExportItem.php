<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\LogExportItemStatus;
use CleverCloud\Sdk\Model\Enum\LogExportKind;

/**
 * One (resource, kind) of a log export.
 */
final readonly class LogExportItem
{
    /**
     * @param int         $readBytes bytes of logs read for this item, before compression
     * @param string|null $reason    why the item was skipped, how it failed, or why a `DONE` item's read may be short
     */
    public function __construct(
        public string $resourceId,
        public LogExportKind $kind,
        public LogExportItemStatus $status,
        public int $lineCount = 0,
        public int $readBytes = 0,
        public ?string $reason = null,
    ) {
    }
}
