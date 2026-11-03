<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\MemberRole;

/**
 * A pending invitation for a user to join an organisation, as returned by
 * `/v2/organisations/{id}/members/invitations`. Dates are RFC 3339 strings.
 */
final readonly class Invitation
{
    public function __construct(
        public string $id,
        public string $organisationId,
        public string $inviteeEmail,
        public string $inviterUserId,
        public MemberRole $role,
        public string $status,
        public string $createdAt,
        public string $expiresAt,
        public ?string $acceptedAt = null,
    ) {
    }
}
