<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class SelfResource extends AbstractV2Resource
{
    /**
     * Returns the raw `/self` payload. Phase 2 replaces this with a typed `User` DTO.
     *
     * @return array<int|string, mixed>
     */
    public function get(): array
    {
        return $this->httpGet('/self');
    }
}
