<?php

namespace CleverCloud\Sdk\Model;

/**
 * A single line item in the consumption report (drops + usage).
 */
final readonly class Consumption
{
    public function __construct(
        public ?string $kind = null,
        public ?string $reason = null,
        public ?string $service = null,
        public ?string $itemId = null,
        public ?int $time = null,
        public ?float $price = null,
        public ?string $currency = null,
        public ?float $weight = null,
    ) {
    }
}
