<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * An instance flavor (size tier): name + resource limits + hourly price.
 */
final readonly class Flavor
{
    /**
     * @param array<string, mixed> $memory Memory descriptor `{value, unit, formatted, ...}`;
     *                                     left as a raw array because the v2 catalog returns
     *                                     a nested object and the public surface keeps it flexible.
     * @param int|null             $nice   scheduler priority, as a Unix nice value (typically -20 to 19)
     */
    public function __construct(
        public string $name,
        public ?int $mem = null,
        public ?int $cpus = null,
        public ?int $gpus = null,
        public ?int $disk = null,
        public ?float $price = null,
        public ?bool $available = null,
        public ?bool $microservice = null,
        #[MapFrom(property: 'machine_learning')]
        public ?bool $machineLearning = null,
        public ?int $nice = null,
        public array $memory = [],
    ) {
    }
}
