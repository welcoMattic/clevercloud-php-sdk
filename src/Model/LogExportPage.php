<?php

namespace CleverCloud\Sdk\Model;

/**
 * One page of log exports, newest first. Pass `$nextCursor` back to
 * `LogExportsResource::list()` to read the next page.
 */
final readonly class LogExportPage
{
    /**
     * @param list<LogExport> $items      the page's exports, without their items
     * @param string|null     $nextCursor cursor of the next page, null on the last one
     */
    public function __construct(
        public array $items = [],
        public ?string $nextCursor = null,
    ) {
    }
}
