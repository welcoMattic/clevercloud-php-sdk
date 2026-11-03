<?php

namespace CleverCloud\Sdk\Model;

/**
 * A network address of a Network Group peer: a host (IP address) and a port.
 */
final readonly class NetworkGroupSocketAddress
{
    public function __construct(
        public string $host,
        public ?int $port = null,
    ) {
    }
}
