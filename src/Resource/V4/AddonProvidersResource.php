<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Provider-agnostic add-on endpoints under `/v4/addon-providers/{providerId}`.
 */
final readonly class AddonProvidersResource extends AbstractV4Resource
{
    /**
     * Returns the add-on as its provider describes it. The shape is defined
     * by each provider, so the payload is returned as decoded JSON.
     *
     * @param string $providerId the add-on provider (`postgresql-addon`, `addon-pulsar`, ...)
     * @param string $realId     the real add-on id (the `realId` field of the `Addon` DTO, e.g. `postgresql_xxx`)
     *
     * @return array<int|string, mixed>
     */
    public function getAddon(string $providerId, string $realId): array
    {
        return $this->httpGet('/addon-providers/'.rawurlencode($providerId).'/addons/'.rawurlencode($realId));
    }
}
