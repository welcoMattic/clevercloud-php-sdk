<?php

namespace CleverCloud\Sdk\Model;

/**
 * A WireGuard node of a Network Group, told apart by `type`: a `CleverPeer`
 * is an instance managed by Clever Cloud (it carries the hypervisor `hv`), an
 * `ExternalPeer` is managed by the customer.
 */
final readonly class NetworkGroupPeer
{
    /**
     * @param list<string> $routedPrefixes IP networks this peer routes, in CIDR notation
     */
    public function __construct(
        public string $id,
        public ?string $type = null,
        public ?string $label = null,
        public ?string $publicKey = null,
        public ?string $hostname = null,
        public ?string $parentMember = null,
        public ?string $parentEvent = null,
        public ?string $hv = null,
        public ?NetworkGroupPeerEndpoint $endpoint = null,
        public array $routedPrefixes = [],
    ) {
    }
}
