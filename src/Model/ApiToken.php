<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * Clever Cloud API token (Bearer credential).
 *
 * Returned by the api-bridge endpoints under `/v2/api-tokens`. The plaintext
 * `token` is only present on creation responses — subsequent reads return only
 * metadata.
 */
final readonly class ApiToken
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $token = null,
        public array $scopes = [],
        #[MapFrom(property: 'created_at')]
        public ?string $createdAt = null,
        #[MapFrom(property: 'expires_at')]
        public ?string $expiresAt = null,
        #[MapFrom(property: 'last_used_at')]
        public ?string $lastUsedAt = null,
        #[MapFrom(property: 'ip_address')]
        public ?string $ipAddress = null,
    ) {
    }
}
