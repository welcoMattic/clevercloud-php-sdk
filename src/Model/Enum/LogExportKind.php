<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * The logs a log export item reads.
 */
enum LogExportKind: string
{
    /** Application and add-on runtime logs. */
    case Logs = 'logs';
    /** HTTP access logs. */
    case AccessLogs = 'accesslogs';
    /** The kind could not be read. */
    case Unknown = 'unknown';
}
