<?php

namespace CleverCloud\Sdk\Model;

/**
 * A zone an owner may create a Kubernetes cluster in, with the deployment
 * profiles it may use there. The full zone is available from
 * `ZonesResource`.
 */
final readonly class KubernetesOwnerZone
{
    /**
     * @param list<array{id: string, prodDefault: bool}> $deploymentProfiles
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $deploymentProfiles = [],
    ) {
    }
}
