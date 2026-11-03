<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\AddonProviderInformation;
use CleverCloud\Sdk\Model\Operator;
use CleverCloud\Sdk\Model\OperatorVersionCheck;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Shared base class for the four operator add-ons exposed under
 * `/v4/addon-providers/addon-{kind}/addons/{id}`: read and lifecycle
 * actions. The API has no collection route there and no update or delete on
 * an add-on: create and delete operators through the V2 add-ons resource.
 * Subclasses declare their operator slug. `$organisationId` is not part of
 * the URL: the API resolves ownership from the caller's credentials.
 */
abstract readonly class AbstractOperatorResource extends AbstractV4Resource
{
    abstract protected function operator(): string;

    /**
     * Path of the provider information endpoint, relative to `/v4`. The API
     * is not consistent across operators (`/addon-providers/keycloak` but
     * `/addon-providers/addon-matomo`), so each operator declares its own.
     */
    abstract protected function providerInformationPath(): string;

    /**
     * Returns the provider as the console shows it: the clusters add-ons are
     * created on and the dedicated versions on offer.
     */
    public function providerInformation(): AddonProviderInformation
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->providerInformationPath());

        return $this->mapTo(AddonProviderInformation::class, $payload);
    }

    public function get(string $id): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->addonPath($id));

        return $this->mapTo(Operator::class, $payload);
    }

    public function reboot(string $id): void
    {
        $this->httpPost($this->addonPath($id).'/reboot');
    }

    public function rebuild(string $id): void
    {
        $this->httpPost($this->addonPath($id).'/rebuild');
    }

    /**
     * Shared implementation of `linkNetworkGroup()` for the operators whose
     * API exposes `networkgroup` (Keycloak, Otoroshi).
     */
    protected function attachNetworkGroup(string $id): void
    {
        $this->httpPost($this->addonPath($id).'/networkgroup');
    }

    /**
     * Shared implementation of `unlinkNetworkGroup()` for the operators whose
     * API exposes `networkgroup` (Keycloak, Otoroshi).
     */
    protected function detachNetworkGroup(string $id): void
    {
        $this->httpDelete($this->addonPath($id).'/networkgroup');
    }

    /**
     * Shared implementation of `checkVersion()` for the operators whose API
     * exposes `version/check` (Keycloak, Metabase, Otoroshi).
     */
    protected function fetchVersionCheck(string $id): OperatorVersionCheck
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->addonPath($id).'/version/check');

        return $this->mapTo(OperatorVersionCheck::class, $payload);
    }

    /**
     * Shared implementation of `updateVersion()` for the operators whose API
     * exposes `version/update` (Keycloak, Metabase, Otoroshi).
     *
     * @return array<string, mixed>
     */
    protected function requestVersionUpdate(string $id, string $targetVersion): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->addonPath($id).'/version/update',
            ['json' => ['targetVersion' => $targetVersion]],
        );

        return $payload;
    }

    protected function addonPath(string $id): string
    {
        return '/addon-providers/addon-'.$this->operator().'/addons/'.rawurlencode($id);
    }
}
