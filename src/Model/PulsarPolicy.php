<?php

namespace CleverCloud\Sdk\Model;

/**
 * Storage policies of a Pulsar add-on's namespace.
 *
 * The API answers `{retentionPolicies, offloadPolicies}`, each side a
 * {@see PulsarStorageLimit} or null when no limit is set. It sends no add-on
 * id: `$addonId` is filled by the SDK from the requested add-on.
 * `$namespace`, `$retentionTimeInMinutes`, `$retentionSizeInMB`,
 * `$messageTTLInSeconds` and `$rawPolicy` are not part of the API response and
 * stay at their defaults.
 */
final readonly class PulsarPolicy
{
    /**
     * @param array<string, mixed> $rawPolicy
     */
    public function __construct(
        public string $addonId,
        public ?string $namespace = null,
        public ?int $retentionTimeInMinutes = null,
        public ?int $retentionSizeInMB = null,
        public ?int $messageTTLInSeconds = null,
        public array $rawPolicy = [],
        public ?PulsarStorageLimit $retentionPolicies = null,
        public ?PulsarStorageLimit $offloadPolicies = null,
    ) {
    }
}
