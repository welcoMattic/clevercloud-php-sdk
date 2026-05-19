<?php

namespace CleverCloud\Sdk\Model;

/**
 * An instance flavor (size tier): name + resource limits + hourly price.
 */
final readonly class Flavor
{
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
        public ?int $memory = null,
    ) {
    }
}
