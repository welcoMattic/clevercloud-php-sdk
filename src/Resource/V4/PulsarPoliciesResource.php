<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\PulsarPolicy;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads and manages Pulsar add-on retention / namespace policies under
 * `/v4/addon-providers/addon-pulsar/...`.
 */
final readonly class PulsarPoliciesResource extends AbstractV4Resource
{
    /**
     * @return list<PulsarPolicy>
     */
    public function list(string $addonId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($addonId).'/policies');

        return $this->mapCollection(PulsarPolicy::class, $payload);
    }

    public function get(string $addonId): PulsarPolicy
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($addonId).'/policy');

        return $this->mapTo(PulsarPolicy::class, $payload);
    }

    /**
     * @param array<string, mixed> $policy
     */
    public function update(string $addonId, array $policy): PulsarPolicy
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->basePath($addonId).'/policy',
            ['json' => $policy],
        );

        return $this->mapTo(PulsarPolicy::class, $payload);
    }

    public function delete(string $addonId): void
    {
        $this->httpDelete($this->basePath($addonId).'/policy');
    }

    private function basePath(string $addonId): string
    {
        return '/addon-providers/addon-pulsar/addons/'.rawurlencode($addonId);
    }
}
