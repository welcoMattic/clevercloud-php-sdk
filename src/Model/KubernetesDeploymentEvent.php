<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesDeploymentEventOperation;
use CleverCloud\Sdk\Model\Enum\KubernetesDeploymentEventStatus;

/**
 * A step of a Kubernetes cluster deployment operation (create, redeploy,
 * upgrade, node group scale, ...).
 */
final readonly class KubernetesDeploymentEvent
{
    /**
     * @param string $createdAt RFC 3339 date-time
     */
    public function __construct(
        public KubernetesDeploymentEventOperation $operation,
        public KubernetesDeploymentEventStatus $status,
        public string $createdAt,
        public ?string $stepName = null,
        public ?string $nodeGroupId = null,
        public ?string $detail = null,
    ) {
    }
}
