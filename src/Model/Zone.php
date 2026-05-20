<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud deployment zone (e.g. `par`, `mtl`, `rbx`).
 */
final readonly class Zone
{
    /**
     * @param list<string> $tags        e.g. `['for:applications', 'infra:ovh', 'green']`
     * @param list<string> $outboundIPs CIDR ranges Clever Cloud sources outbound traffic from
     */
    public function __construct(
        public string $name,
        public ?string $id = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $countryCode = null,
        public ?string $displayName = null,
        public ?float $lat = null,
        public ?float $lon = null,
        public array $tags = [],
        public array $outboundIPs = [],
    ) {
    }
}
