<?php

namespace CleverCloud\Sdk\Model;

/**
 * TCP port redirection bound to a Clever Cloud application.
 *
 * Returned by `/organisations/{ownerId}/applications/{appId}/tcp-redirs`. The
 * `namespace` identifies the routing layer that exposes the port (e.g.
 * `default`, `cleverapps`, `customer-X`).
 */
final readonly class TcpRedirection
{
    public function __construct(
        public int $port,
        public string $namespace,
    ) {
    }
}
