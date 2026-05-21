<?php

namespace CleverCloud\Sdk\Model;

/**
 * An instance flavor (size tier): name + resource limits + hourly price.
 */
final readonly class Flavor
{
    /**
     * @param array<string, mixed> $memory Memory descriptor `{value, unit, formatted, ...}`;
     *                                     left as a raw array because the v2 catalog returns
     *                                     a nested object and the public surface keeps it flexible.
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
        public ?bool $machineLearning = null,
        public ?bool $nice = null,
        public array $memory = [],
    ) {
    }
}
