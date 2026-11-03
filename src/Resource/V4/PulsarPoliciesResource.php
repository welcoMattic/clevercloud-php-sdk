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

        return $this->mapPolicy($addonId, $payload);
    }

    /**
     * Sets the namespace retention policy and, when `offloadPolicies` is
     * sent, its offload thresholds. Sizes are in bytes, durations in minutes:
     * `{"retentionPolicies": {"durationInMinutes": 1440, "sizeInBytes": 1073741824}}`.
     * Returns the policies the cluster then applies.
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

        return $this->mapPolicy($addonId, $payload);
    }

    /**
     * Resets the policy to provider defaults.
     */
    public function reset(string $addonId): void
    {
        $this->httpDelete($this->policyPath($addonId));
    }

    /**
     * The API answers `{retentionPolicies, offloadPolicies}` without the
     * add-on id, so the requested one is injected before mapping.
     *
     * @param array<string, mixed> $payload
     */
    private function mapPolicy(string $addonId, array $payload): PulsarPolicy
    {
        return $this->mapTo(PulsarPolicy::class, $payload + ['addonId' => $addonId]);
    }

    private function policyPath(string $addonId): string
    {
        return '/addon-providers/addon-pulsar/addons/'.rawurlencode($addonId).'/storage-policies';
    }
}
