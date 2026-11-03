<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeStatus;

/**
 * A standalone node of a Kubernetes cluster (a node outside any node group),
 * as returned by
 * `/v4/kubernetes/organisations/{ownerId}/clusters/{kubernetesId}/nodes[/{nodeId}]`.
 */
final readonly class KubernetesNode
{
    /**
     * @param array<string, string> $labels Kubernetes labels applied to the node
     * @param list<KubernetesTaint> $taints Kubernetes taints applied to the node
     */
    public function __construct(
        public string $id,
        public string $clusterId,
        public string $name,
        public KubernetesNodeFlavor $flavor,
        public KubernetesNodeStatus $status,
        public ?string $description = null,
        public ?string $tag = null,
        public array $labels = [],
        public array $taints = [],
    ) {
    }
}
