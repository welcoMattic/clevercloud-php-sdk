<?php

namespace CleverCloud\Sdk\Model;

/**
 * Storage policies of a Pulsar add-on's namespace.
 *
 * The API answers `{retentionPolicies, offloadPolicies}`, each side a
 * {@see PulsarStorageLimit} or null when no limit is set. It sends no add-on
 * id: `$addonId` is filled by the SDK from the requested add-on.
 */
final readonly class PulsarPolicy
{
    public function __construct(
        public string $addonId,
        public ?PulsarStorageLimit $retentionPolicies = null,
        public ?PulsarStorageLimit $offloadPolicies = null,
    ) {
    }
}
