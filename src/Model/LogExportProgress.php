<?php

namespace CleverCloud\Sdk\Model;

/**
 * How far a log export has gone.
 */
final readonly class LogExportProgress
{
    /**
     * @param int $readBytes bytes of logs read so far, before compression
     */
    public function __construct(
        public int $itemsTotal = 0,
        public int $itemsDone = 0,
        public int $itemsSkipped = 0,
        public int $itemsFailed = 0,
        public int $lineCount = 0,
        public int $readBytes = 0,
    ) {
    }
}
