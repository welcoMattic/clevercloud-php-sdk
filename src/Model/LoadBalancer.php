<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud load balancer fronting an application's instances.
 *
 * @phpstan-type DnsInfoShape array{
 *     hostname?: string,
 *     ipAddresses?: list<string>,
 *     ipv4?: list<string>,
 *     ipv6?: list<string>,
 *     aRecords?: list<string>,
 *     aaaaRecords?: list<string>,
 *     cnameRecords?: list<string>,
 * }
 */
final readonly class LoadBalancer
{
    /**
     * @param array<string, mixed> $dns
     */
    public function __construct(
        public string $id,
        public ?string $kind = null,
        public ?bool $isDefault = null,
        public array $dns = [],
    ) {
    }
}
