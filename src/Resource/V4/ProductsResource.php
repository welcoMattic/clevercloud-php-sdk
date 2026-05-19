<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\AddonProvider;
use CleverCloud\Sdk\Model\Country;
use CleverCloud\Sdk\Model\InstanceType;
use CleverCloud\Sdk\Model\Zone;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads the public product catalog: instance types, add-on providers, zones,
 * and countries. None of these are owner-scoped.
 */
final readonly class ProductsResource extends AbstractV4Resource
{
    /**
     * @return list<InstanceType>
     */
    public function instances(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/instances');

        return $this->mapCollection(InstanceType::class, $payload);
    }

    /**
     * @return list<AddonProvider>
     */
    public function addonProviders(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/addonproviders');

        return $this->mapCollection(AddonProvider::class, $payload);
    }

    /**
     * @return list<Zone>
     */
    public function zones(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/zones');

        return $this->mapCollection(Zone::class, $payload);
    }

    /**
     * @return list<Country>
     */
    public function countries(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/countries');

        return $this->mapCollection(Country::class, $payload);
    }
}
