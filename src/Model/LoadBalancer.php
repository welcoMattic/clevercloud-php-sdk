<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud load balancer serving an application, with the DNS records
 * to point a domain at it.
 */
final readonly class LoadBalancer
{
    /**
     * @param string               $zoneId id of the {@see Zone} the load balancer runs in
     * @param array<string, mixed> $dns    `cname` (target of a CNAME record) and `a` (IPv4 addresses for A records)
     */
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $zoneId = null,
        public array $dns = [],
    ) {
    }
}
