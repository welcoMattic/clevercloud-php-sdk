<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\User;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class UsersResource extends AbstractV2Resource
{
    public function get(string $userId): User
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/users/'.rawurlencode($userId));

        return $this->mapTo(User::class, $payload);
    }

    /**
     * Returns the raw application payloads for a user. Phase 3 typed-maps these
     * into Application DTOs.
     *
     * @return list<array<string, mixed>>
     */
    public function applications(string $userId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/users/'.rawurlencode($userId).'/applications');

        return $payload;
    }

    /**
     * Returns the raw add-on payloads for a user. Phase 3 typed-maps these
     * into Addon DTOs.
     *
     * @return list<array<string, mixed>>
     */
    public function addons(string $userId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/users/'.rawurlencode($userId).'/addons');

        return $payload;
    }
}
