<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Where one (resource, kind) of a log export stands, as reported on
 * `LogExportItem::$status`.
 */
enum LogExportItemStatus: string
{
    /** Not read yet. */
    case Pending = 'PENDING';
    /** Being read. */
    case Running = 'RUNNING';
    /** Read, its file is in the archive. */
    case Done = 'DONE';
    /** Could not be read, see `reason`; the rest of the export goes on. */
    case Failed = 'FAILED';
    /** The resource has no logs of this kind, see `reason`. */
    case Skipped = 'SKIPPED';
    /** The status could not be read. */
    case Unknown = 'UNKNOWN';
}
