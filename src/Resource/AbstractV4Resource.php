<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;

abstract readonly class AbstractV4Resource extends AbstractResource
{
    final protected function version(): ApiVersion
    {
        return ApiVersion::V4;
    }

    /**
     * Builds the `/self` or `/organisations/{id}` path prefix for V4
     * endpoints that scope by owner. Pass `null` for the current user.
     */
    final protected function ownerPath(?string $organisationId): string
    {
        if (null === $organisationId) {
            return '/self';
        }

        return '/organisations/'.rawurlencode($organisationId);
    }
}
