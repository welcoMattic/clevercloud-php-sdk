<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\ApiException;

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

    /**
     * Returns `$organisationId` unchanged, or the personal organisation id
     * (`user_<uuid>`) read from `GET /v2/self` when it is null. For the
     * endpoints that have no `/self` variant and only accept an explicit
     * owner id.
     *
     * @throws ApiException when the personal organisation id cannot be resolved
     */
    final protected function resolveOwnerId(?string $organisationId): string
    {
        if (null !== $organisationId) {
            return $organisationId;
        }

        $payload = $this->httpGet('/self');

        if (!isset($payload['id']) || !\is_string($payload['id']) || '' === $payload['id']) {
            throw new ApiException('Could not resolve personal organisation id from /v2/self response', 0);
        }

        return $payload['id'];
    }
}
