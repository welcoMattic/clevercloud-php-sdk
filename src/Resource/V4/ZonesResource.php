<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Zone;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Thin alias around `/v4/products/zones[/...]` for ergonomic access.
 */
final readonly class ZonesResource extends AbstractV4Resource
{
    /**
     * @return list<Zone>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/zones');

        return $this->mapCollection(Zone::class, $payload);
    }

    public function get(string $name): Zone
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/products/zones/'.rawurlencode($name));

        return $this->mapTo(Zone::class, $payload);
    }
}
