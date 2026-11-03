<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Materia KV database: its cluster, owner, plan, connection host and port
 * and access token. Returned by
 * `/v4/materia/organisations/{ownerId}/materia/databases/{kvId}`.
 *
 * `token` is also the password of the Redis-compatible endpoint: treat it as
 * a secret.
 */
final readonly class MateriaKvDatabase
{
    public function __construct(
        public string $id,
        public ?string $clusterId = null,
        public ?string $ownerId = null,
        public ?string $kind = null,
        public ?string $plan = null,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $token = null,
        public ?string $tokenId = null,
        public ?string $status = null,
    ) {
    }
}
