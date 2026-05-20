<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\PulsarPolicy;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads and manages Pulsar add-on storage policies (retention, offload,
 * message TTL) under
 * `/v4/addon-providers/addon-pulsar/addons/{addonId}/storage-policies`.
 */
final readonly class PulsarPoliciesResource extends AbstractV4Resource
{
    public function get(string $addonId): PulsarPolicy
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->policyPath($addonId));

        return $this->mapTo(PulsarPolicy::class, $payload);
    }

    /**
     * Partial update — only the fields you pass are touched.
     *
     * @param array<string, mixed> $policy
     */
    public function update(string $addonId, array $policy): PulsarPolicy
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPatch(
            $this->policyPath($addonId),
            ['json' => $policy],
        );

        return $this->mapTo(PulsarPolicy::class, $payload);
    }

    /**
     * Resets the policy to provider defaults.
     */
    public function reset(string $addonId): void
    {
        $this->httpDelete($this->policyPath($addonId));
    }

    private function policyPath(string $addonId): string
    {
        return '/addon-providers/addon-pulsar/addons/'.rawurlencode($addonId).'/storage-policies';
    }
}
