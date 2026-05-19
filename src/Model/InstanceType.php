<?php

namespace CleverCloud\Sdk\Model;

/**
 * A catalog entry describing an instance type (node, php, python, …) and the
 * flavors it can run on. Returned by `/v4/products/instances`.
 */
final readonly class InstanceType
{
    /**
     * @param list<Flavor> $flavors
     * @param list<string> $variants
     */
    public function __construct(
        public string $type,
        public ?string $version = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?bool $enabled = null,
        public ?bool $comingSoon = null,
        public ?int $maxInstances = null,
        public array $flavors = [],
        public array $variants = [],
    ) {
    }
}
