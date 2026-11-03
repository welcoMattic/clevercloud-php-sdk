<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;

abstract readonly class AbstractV2Resource extends AbstractResource
{
    final protected function version(): ApiVersion
    {
        return ApiVersion::V2;
    }

    /**
     * Builds the `/self` or `/organisations/{id}` path prefix used by most
     * V2 application / add-on / deployment endpoints. Pass `null` for the
     * current user, an organisation id otherwise.
     */
    final protected function ownerPath(?string $organisationId): string
    {
        if (null === $organisationId) {
            return '/self';
        }

        return '/organisations/'.rawurlencode($organisationId);
    }
}
