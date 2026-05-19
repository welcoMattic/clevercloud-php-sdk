<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\MemberRole;

/**
 * A member of an Organisation, returned by `/v2/organisations/{id}/members`.
 *
 * The nested {@see User} mirrors the `member` sub-object Clever Cloud embeds in
 * the response.
 */
final readonly class Member
{
    public function __construct(
        public User $member,
        public MemberRole $role,
        public ?string $job = null,
    ) {
    }
}
