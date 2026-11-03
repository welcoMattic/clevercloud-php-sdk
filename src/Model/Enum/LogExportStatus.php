<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Where a log export stands, as reported on `LogExport::$status`.
 */
enum LogExportStatus: string
{
    /** Accepted, waiting for a worker. */
    case Pending = 'PENDING';
    /** The logs are being read. */
    case Running = 'RUNNING';
    /** The logs are read, the archive is being written. */
    case Assembling = 'ASSEMBLING';
    /** The archive can be downloaded until `expiresAt`. */
    case Done = 'DONE';
    /** Stopped, see `LogExport::$error`. */
    case Failed = 'FAILED';
    /** Cancelled, or its archive deleted, by a `DELETE`. */
    case Cancelled = 'CANCELLED';
    /** The archive was deleted from storage. */
    case Expired = 'EXPIRED';
    /** The status could not be read: poll again. */
    case Unknown = 'UNKNOWN';

    /**
     * Whether the export will not move anymore, so polling can stop.
     */
    public function isTerminal(): bool
    {
        return self::Done === $this || self::Failed === $this || self::Cancelled === $this || self::Expired === $this;
    }
}
