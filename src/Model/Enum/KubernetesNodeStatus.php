<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Lifecycle status of a Kubernetes node.
 */
enum KubernetesNodeStatus: string
{
    case Deploying = 'DEPLOYING';
    case Deployed = 'DEPLOYED';
    case Ready = 'READY';
    case Draining = 'DRAINING';
    case Failed = 'FAILED';
    case Deleted = 'DELETED';
}
