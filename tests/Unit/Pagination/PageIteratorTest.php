<?php

namespace CleverCloud\Sdk\Tests\Unit\Pagination;

use CleverCloud\Sdk\Pagination\PageIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageIterator::class)]
final class PageIteratorTest extends TestCase
{
    public function testYieldsInitialItemsOnly(): void
    {
        $page = new PageIterator(['a', 'b', 'c']);

        self::assertSame(['a', 'b', 'c'], $page->toList());
    }

    public function testYieldsEmptyWhenNoInitialAndNoPager(): void
    {
        $page = new PageIterator();

        self::assertSame([], $page->toList());
    }

    public function testFollowsPagerUntilCursorIsNull(): void
    {
        $pages = [
            'cur-1' => [['item-1', 'item-2'], 'cur-2'],
            'cur-2' => [['item-3'], null],
        ];
        $pager = static fn (?string $cursor): array => $pages[$cursor ?? ''];

        $page = new PageIterator(
            initial: ['item-0'],
            pager: $pager,
            nextCursor: 'cur-1',
        );

        self::assertSame(['item-0', 'item-1', 'item-2', 'item-3'], $page->toList());
    }

    public function testStopsImmediatelyWhenNextCursorIsNull(): void
    {
        $invocations = 0;
        $pager = static function () use (&$invocations): array {
            ++$invocations;

            return [[], null];
        };

        $page = new PageIterator(initial: ['a'], pager: $pager);

        self::assertSame(['a'], $page->toList());
        self::assertSame(0, $invocations);
    }
}
