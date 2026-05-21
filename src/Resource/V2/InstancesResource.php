<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\InstanceType;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Lists the runtime instance types Clever Cloud exposes (node, php, python,
 * docker, …) along with their available flavors and variants.
 *
 * Lives on the V2 API per `https://api.clever-cloud.com/v2/products/instances`.
 * The same payload is also available via {@see ProductsResource::instances()};
 * this resource is kept as an ergonomic alias.
 *
 * The `get(type[, version])`, `flavors(type)` and `types()` methods that
 * existed in 1.0.0 have been removed in 1.0.1 — they targeted V4 routes that
 * Clever Cloud does not expose (all returned 404). To get the variants and
 * flavors for a single type, call {@see list()} and filter client-side on
 * `$instanceType->type`.
 */
final readonly class InstancesResource extends AbstractV2Resource
{
    /**
     * @return list<InstanceType>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/instances');

        return $this->mapCollection(InstanceType::class, $payload);
    }
}
