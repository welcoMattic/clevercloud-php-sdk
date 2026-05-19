<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Flavor;
use CleverCloud\Sdk\Model\InstanceType;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads catalog metadata for runtime instance types and their flavors.
 *
 * The naming clashes with V2's per-app instances endpoint — that one is on
 * {@see \CleverCloud\Sdk\Resource\V2\ApplicationsResource::instances()}; this
 * one is the runtime / flavor catalog.
 */
final readonly class InstancesResource extends AbstractV4Resource
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

    public function get(string $type, ?string $version = null): InstanceType
    {
        $path = '/products/instances/'.rawurlencode($type);
        if (null !== $version) {
            $path .= '/'.rawurlencode($version);
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($path);

        return $this->mapTo(InstanceType::class, $payload);
    }

    /**
     * Lists the flavors a given instance type can run on.
     *
     * @return list<Flavor>
     */
    public function flavors(string $type): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/instances/'.rawurlencode($type).'/flavors');

        return $this->mapCollection(Flavor::class, $payload);
    }

    /**
     * Lists all available runtime types (short names: node, php, python, …).
     *
     * @return list<string>
     */
    public function types(): array
    {
        /** @var list<string> $payload */
        $payload = $this->httpGet('/products/instances/types');

        return $payload;
    }
}
