<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeGroupStatus;

/**
 * A node group (or standalone node group) as embedded in a
 * {@see KubernetesCluster} detail response. Use
 * `KubernetesResource::getNodeGroup()` for the full {@see KubernetesNodeGroup}.
 */
final readonly class KubernetesClusterNodeGroup
{
    /**
     * @param list<KubernetesClusterNode> $nodes always empty for standalone node groups
     */
    public function __construct(
        public string $id,
        public string $name,
        public KubernetesNodeGroupStatus $status,
        public KubernetesNodeFlavor $flavor,
        public int $currentNodeCount,
        public int $targetNodeCount,
        public ?KubernetesFailure $failure = null,
        public array $nodes = [],
    ) {
    }
}
