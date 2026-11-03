<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\ServiceTokenStatus;

/**
 * Metadata of an organisation service token (biscuit), as returned by
 * `/v2/organisations/{id}/service-tokens`. The token value itself is never
 * part of it: see {@see CreatedServiceToken}.
 *
 * The API does not echo the role nor the resource restriction given at
 * creation: both are carried by the token itself.
 */
final readonly class ServiceToken
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $id,
        /** The owning organisation as a `tenant_...` ID (its `orga_` prefix swapped for `tenant_`). */
        public string $tenantId,
        /** Revocation ID, accepted in place of {@see $id} to read or revoke the token. */
        public string $revocationId,
        public ServiceTokenStatus $status,
        /** RFC 3339 creation date. */
        public string $createdAt,
        public ?string $name = null,
        public ?string $description = null,
        /** `SELF_SUSTAINED_ACCESS` for a token created through the service-token route. */
        public ?string $tokenType = null,
        public ?string $productId = null,
        public ?string $locationId = null,
        public ?string $resourceId = null,
        public array $tags = [],
        /** Identity that issued the token on the platform's side. */
        public ?string $instigatorId = null,
        /** RFC 3339 expiry date. */
        public ?string $expiredAt = null,
        /** RFC 3339 revocation date, `null` while the token is not revoked. */
        public ?string $revokedAt = null,
        /** `user_...` ID of the member who created the token; only set on list / get responses, `null` when unknown. */
        public ?string $creatorUserId = null,
    ) {
    }
}
