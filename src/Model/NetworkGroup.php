<?php

namespace CleverCloud\Sdk\Model;

/**
 * A WireGuard-based private network linking apps, add-ons and external peers.
 *
 * `name` and `cidr` predate the published OpenAPI spec, which defines neither:
 * the API sends the group name as `label` and its CIDR block as `networkIp`.
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
        public ?string $name = null,
        public ?string $description = null,
        public ?string $ownerId = null,
        public ?string $networkIp = null,
        public ?string $lastAllocatedIp = null,
        public ?string $cidr = null,
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
