<?php

namespace CleverCloud\Sdk\Pagination;

use Closure;
use Generator;
use IteratorAggregate;

/**
 * Lazily iterates a paginated API endpoint without materialising every page in
 * memory. Most Clever Cloud `/v2` list endpoints return all results in one
 * response (in which case `PageIterator` just yields its preloaded items), but
 * v4 endpoints frequently use cursor-based paging — the optional `$pager`
 * closure handles that case.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class PageIterator implements IteratorAggregate
{
    /**
     * @param list<T>                                            $initial first page already loaded
     * @param ?Closure(?string $cursor): array{list<T>, ?string} $pager   returns `[items, nextCursor]`;
     *                                                                    `null` next-cursor stops iteration
     */
    public function __construct(
        private array $initial = [],
        private ?Closure $pager = null,
        private ?string $nextCursor = null,
    ) {
    }

    public function getIterator(): Generator
    {
        foreach ($this->initial as $item) {
            yield $item;
        }

        $cursor = $this->nextCursor;
        $pager = $this->pager;
        if (null === $pager) {
            return;
        }

        while (null !== $cursor) {
            [$items, $cursor] = $pager($cursor);
            foreach ($items as $item) {
                yield $item;
            }
        }
    }

    /**
     * Materialises the iterator into a plain list. Useful when you genuinely
     * need an array (json_encode, count, etc).
     *
     * @return list<T>
     */
    public function toList(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }
}
