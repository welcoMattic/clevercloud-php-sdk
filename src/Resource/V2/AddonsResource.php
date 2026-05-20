<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Addon;
use CleverCloud\Sdk\Model\AddonProvider;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class AddonsResource extends AbstractV2Resource
{
    /**
     * @return list<Addon>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->ownerPath($organisationId).'/addons');

        return $this->mapCollection(Addon::class, $payload);
    }

    public function get(string $addonId, ?string $organisationId = null): Addon
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->addonPath($addonId, $organisationId));

        return $this->mapTo(Addon::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, region, providerId, plan}
     */
    public function create(array $data, ?string $organisationId = null): Addon
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->ownerPath($organisationId).'/addons',
            ['json' => $data],
        );

        return $this->mapTo(Addon::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $addonId, array $data, ?string $organisationId = null): Addon
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->addonPath($addonId, $organisationId),
            ['json' => $data],
        );

        return $this->mapTo(Addon::class, $payload);
    }

    public function delete(string $addonId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->addonPath($addonId, $organisationId));
    }

    /**
     * @return list<AddonProvider>
     */
    public function providers(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/addonproviders');

        return $this->mapCollection(AddonProvider::class, $payload);
    }

    public function provider(string $providerId): AddonProvider
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/products/addonproviders/'.rawurlencode($providerId));

        return $this->mapTo(AddonProvider::class, $payload);
    }

    /**
     * Plans available for a given add-on provider.
     *
     * @return list<array<string, mixed>>
     */
    public function plans(string $providerId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/products/addonproviders/'.rawurlencode($providerId).'/plans');

        return $payload;
    }

    /**
     * Environment variables exposed by an add-on (connection strings, …).
     *
     * @return array<string, string>
     */
    public function env(string $addonId, ?string $organisationId = null): array
    {
        /** @var list<array{name: string, value: string}> $payload */
        $payload = $this->httpGet($this->addonPath($addonId, $organisationId).'/env');

        $map = [];
        foreach ($payload as $entry) {
            if (isset($entry['name'], $entry['value']) && \is_string($entry['name']) && \is_string($entry['value'])) {
                $map[$entry['name']] = $entry['value'];
            }
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function linkedApplications(string $addonId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->addonPath($addonId, $organisationId).'/applications');

        return $payload;
    }

    /**
     * Returns the SSO payload (signed URL + params) for add-ons that provide
     * a single-sign-on web UI (Pulsar, Cellar, Matomo, etc.).
     *
     * @return array<string, mixed>
     */
    public function sso(string $addonId, ?string $organisationId = null): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->addonPath($addonId, $organisationId).'/sso');

        return $payload;
    }

    /**
     * @return list<string>
     */
    public function tags(string $addonId, ?string $organisationId = null): array
    {
        /** @var list<string> $payload */
        $payload = $this->httpGet($this->addonPath($addonId, $organisationId).'/tags');

        return $payload;
    }

    public function addTag(string $addonId, string $tag, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->addonPath($addonId, $organisationId).'/tags/'.rawurlencode($tag),
        );
    }

    public function removeTag(string $addonId, string $tag, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->addonPath($addonId, $organisationId).'/tags/'.rawurlencode($tag),
        );
    }

    /**
     * Migrates an add-on to a different plan (vertical scaling).
     */
    public function migrate(string $addonId, string $targetPlanId, ?string $organisationId = null): Addon
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->addonPath($addonId, $organisationId).'/migrations',
            ['json' => ['plan' => $targetPlanId]],
        );

        return $this->mapTo(Addon::class, $payload);
    }

    private function addonPath(string $addonId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/addons/'.rawurlencode($addonId);
    }
}
