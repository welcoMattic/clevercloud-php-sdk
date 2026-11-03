<?php

namespace CleverCloud\Sdk\Model;

/**
 * A cluster an add-on provider creates add-ons on, as listed by
 * {@see AddonProviderInformation}.
 */
final readonly class AddonProviderCluster
{
    /**
     * @param list<string> $features
     */
    public function __construct(
        public string $id,
        public ?string $label = null,
        public ?string $zone = null,
        public ?string $version = null,
        public array $features = [],
    ) {
    }
}
