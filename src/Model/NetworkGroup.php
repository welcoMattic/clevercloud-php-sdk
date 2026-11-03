<?php

namespace CleverCloud\Sdk\Model;

/**
 * A WireGuard-based private network linking apps, add-ons and external peers.
 * Its name is `$label`, its CIDR block `$networkIp`.
 */
final readonly class NetworkGroup
{
    /**
     * @param list<NetworkGroupMember> $members
     * @param list<string>             $tags
     * @param list<NetworkGroupPeer>   $peers
     */
    public function __construct(
        public string $id,
        public ?string $description = null,
        public ?string $ownerId = null,
        public ?string $networkIp = null,
        public ?string $lastAllocatedIp = null,
        public array $members = [],
        public ?string $label = null,
        public ?string $dnsSanitizedLabel = null,
        public ?string $ownership = null,
        public array $tags = [],
        public array $peers = [],
        public ?int $version = null,
    ) {
    }
}
