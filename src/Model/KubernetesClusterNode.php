<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesNodeStatus;

/**
 * A node of a node group, as embedded in a {@see KubernetesCluster} detail
 * response.
 */
final readonly class KubernetesClusterNode
{
    public function __construct(
        public string $id,
        public string $name,
        public KubernetesNodeStatus $status,
        public int $vCpus,
        public int $ramBytes,
    ) {
    }
}
