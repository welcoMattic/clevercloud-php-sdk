<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesClusterStatus;

/**
 * A managed Kubernetes cluster, as returned by
 * `/v4/kubernetes/organisations/{ownerId}/clusters[/{kubernetesId}]`.
 *
 * `apiServerUrl`, `loadBalancers`, `nodeGroups` and `standaloneNodeGroups`
 * are only filled on the detail response (`KubernetesResource::getCluster()`);
 * list and mutation responses leave them empty.
 */
final readonly class KubernetesCluster
{
    /**
     * @param list<string>                     $tags                 semantic tags (`tag` or `key:value`)
     * @param array<string, mixed>             $topologyConfig       control-plane topology, tagged by `topology`
     *                                                               (`ALL_IN_ONE`, `DEDICATED_COMPUTE` or `DISTRIBUTED`);
     *                                                               left raw because its shape depends on the tag
     * @param list<KubernetesLoadBalancer>     $loadBalancers
     * @param list<KubernetesClusterNodeGroup> $nodeGroups
     * @param list<KubernetesClusterNodeGroup> $standaloneNodeGroups
     * @param array<string, mixed>|null        $waiting              set while the cluster waits on a dependency
     *                                                               (`dependency`, `since`, `attempts`, ...)
     * @param string|null                      $locationId           deprecated by the API: always equal to `$zoneId`
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $name,
        public KubernetesClusterStatus $status,
        public string $creationDate,
        public string $version,
        public array $tags = [],
        public array $topologyConfig = [],
        public ?string $description = null,
        public ?string $apiServerUrl = null,
        public ?string $deploymentProfileId = null,
        public ?string $zoneId = null,
        public ?string $locationId = null,
        public ?int $storageUsageBytes = null,
        public ?KubernetesClusterFeatures $features = null,
        public ?KubernetesFailure $failure = null,
        public ?array $waiting = null,
        public array $loadBalancers = [],
        public array $nodeGroups = [],
        public array $standaloneNodeGroups = [],
    ) {
    }
}
