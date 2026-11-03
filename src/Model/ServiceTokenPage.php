<?php

namespace CleverCloud\Sdk\Model;

/**
 * One cursor page of service tokens. Pass {@see $nextCursor} back to fetch
 * the next page; it is `null` on the last one.
 */
final readonly class ServiceTokenPage
{
    /**
     * @param list<ServiceToken> $items
     */
    public function __construct(
        public array $items = [],
        public ?string $nextCursor = null,
    ) {
    }
}
