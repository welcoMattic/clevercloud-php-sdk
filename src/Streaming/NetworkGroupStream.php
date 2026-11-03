<?php

namespace CleverCloud\Sdk\Streaming;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Model\NetworkGroup;
use Generator;
use IteratorAggregate;

/**
 * Yields a typed {@see NetworkGroup} every time the watched Network Group
 * changes, as pushed by `GET .../networkgroups/{networkGroupId}/stream`.
 *
 * @implements IteratorAggregate<int, NetworkGroup>
 */
final readonly class NetworkGroupStream implements IteratorAggregate
{
    public function __construct(
        private EventStream $events,
        private AutoMapperInterface $mapper,
    ) {
    }

    /**
     * @return Generator<int, NetworkGroup, mixed, void>
     */
    public function getIterator(): Generator
    {
        foreach ($this->events as $event) {
            $payload = $event->data;
            if (!isset($payload['id']) || !\is_string($payload['id'])) {
                continue;
            }

            $networkGroup = $this->mapper->map($payload, NetworkGroup::class);
            if ($networkGroup instanceof NetworkGroup) {
                yield $networkGroup;
            }
        }
    }
}
