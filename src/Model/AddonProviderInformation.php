<?php

namespace CleverCloud\Sdk\Model;

/**
 * An add-on provider as the console shows it: the clusters add-ons are
 * created on and the dedicated versions it offers. Returned by the provider
 * information endpoints of the operators (`/v4/addon-providers/keycloak`,
 * `/v4/addon-providers/addon-matomo`, ...) and of Materia KV
 * (`/v4/addon-providers/kv`).
 */
final readonly class AddonProviderInformation
{
    /**
     * @param list<AddonProviderCluster>          $clusters
     * @param array<string, array<string, mixed>> $dedicated offered dedicated versions keyed by version, each with its `features`
     */
    public function __construct(
        public string $providerId,
        public ?string $defaultDedicatedVersion = null,
        public array $clusters = [],
        public array $dedicated = [],
    ) {
    }
}
