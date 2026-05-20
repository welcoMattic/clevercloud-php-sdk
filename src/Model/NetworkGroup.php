<?php

namespace CleverCloud\Sdk\Model;

/**
 * A WireGuard-based private network linking apps, add-ons and external peers.
 */
final readonly class NetworkGroup
{
    /**
     * @param list<NetworkGroupMember> $members
     */
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $ownerId = null,
        public ?string $networkIp = null,
        public ?string $lastAllocatedIp = null,
        public ?string $cidr = null,
        public array $members = [],
    ) {
    }
}
