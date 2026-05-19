<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\User;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class SelfResource extends AbstractV2Resource
{
    public function get(): User
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self');

        return $this->mapTo(User::class, $payload);
    }
}
