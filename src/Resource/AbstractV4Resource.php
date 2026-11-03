<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\ApiException;

abstract readonly class AbstractV4Resource extends AbstractResource
{
    final protected function version(): ApiVersion
    {
        return ApiVersion::V4;
    }

    /**
     * Builds the `/organisations/{id}` path prefix for V4 endpoints that scope
     * by owner. Pass `null` for the current user: V4 has no `/self` variant,
     * so the personal `user_...` id is resolved through `GET /v2/self`.
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    final protected function ownerPath(?string $organisationId): string
    {
        return '/organisations/'.rawurlencode($this->resolveOwnerId($organisationId));
    }
}
