<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud deployment zone (e.g. `par`, `mtl`, `rbx`).
 */
final readonly class Zone
{
    public function __construct(
        public string $name,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $countryCode = null,
        public ?string $tags = null,
        public ?string $displayName = null,
        public ?float $lat = null,
        public ?float $lon = null,
    ) {
    }
}
