<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\AddonProviderInformation;
use CleverCloud\Sdk\Model\Operator;
use CleverCloud\Sdk\Model\OperatorVersionCheck;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Shared CRUD + lifecycle base class for the four operator add-ons exposed
 * under `/v4/addon-providers/addon-{kind}/addons[/{id}]`. Subclasses declare
 * their operator slug. `$organisationId` is not part of the URL — the API
 * resolves ownership from the caller's credentials.
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

    /**
     * @return list<Operator>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath());

        return $this->mapCollection(Operator::class, $payload);
    }

    public function get(string $id): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath().'/'.rawurlencode($id));

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, region, planId, ...}
     */
    public function create(array $data): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath(), ['json' => $data]);

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->basePath().'/'.rawurlencode($id),
            ['json' => $data],
        );

        return $this->mapTo(Operator::class, $payload);
    }

    public function delete(string $id): void
    {
        $this->httpDelete($this->basePath().'/'.rawurlencode($id));
    }

    public function reboot(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/reboot');
    }

    public function rebuild(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/rebuild');
    }

    /**
     * Links the operator to a Network Group (Keycloak / Otoroshi only).
     */
    public function linkNetworkGroup(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/networkgroup');
    }

    public function unlinkNetworkGroup(string $id): void
    {
        $this->httpDelete($this->basePath().'/'.rawurlencode($id).'/networkgroup');
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
        return $this->basePath().'/'.rawurlencode($id);
    }

    private function basePath(): string
    {
        return '/addon-providers/addon-'.$this->operator().'/addons';
    }
}
