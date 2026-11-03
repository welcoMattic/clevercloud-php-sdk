<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Status of a step in a Kubernetes deployment.
 */
enum KubernetesDeploymentEventStatus: string
{
    case Started = 'STARTED';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';
    case Skipped = 'SKIPPED';
}
