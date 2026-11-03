<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeGroupStatus;

/**
 * A group of identical worker nodes in a Kubernetes cluster, as returned by
 * `/v4/kubernetes/organisations/{ownerId}/clusters/{kubernetesId}/node-groups[/{nodeGroupId}]`.
 */
final readonly class KubernetesNodeGroup
{
    /**
     * @param int                   $currentNodeCount number of nodes in the `READY` status
     * @param array<string, string> $labels           Kubernetes labels applied to the nodes
     * @param list<KubernetesTaint> $taints           Kubernetes taints applied to the nodes
     */
    public function __construct(
        public string $id,
        public string $clusterId,
        public string $name,
        public KubernetesNodeFlavor $flavor,
        public KubernetesNodeGroupStatus $status,
        public int $currentNodeCount,
        public int $targetNodeCount,
        public int $minNodeCount,
        public int $maxNodeCount,
        public bool $autoscalingEnabled,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $description = null,
        public ?string $tag = null,
        public array $labels = [],
        public array $taints = [],
        public ?KubernetesFailure $failure = null,
    ) {
    }
}
