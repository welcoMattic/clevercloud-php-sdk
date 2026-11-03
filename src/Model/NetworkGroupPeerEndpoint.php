<?php

namespace CleverCloud\Sdk\Model;

/**
 * Where a Network Group peer sits in the mesh, told apart by `type`.
 *
 * A `ClientEndpoint` only carries its mesh address (`ngIp`). A
 * `ServerEndpoint` listens on `ngTerm` (mesh address and WireGuard port) and
 * is reached from outside the mesh on `publicTerm`.
 */
final readonly class NetworkGroupPeerEndpoint
{
    public function __construct(
        public ?string $type = null,
        public ?string $ngIp = null,
        public ?NetworkGroupSocketAddress $ngTerm = null,
        public ?NetworkGroupSocketAddress $publicTerm = null,
    ) {
    }
}
