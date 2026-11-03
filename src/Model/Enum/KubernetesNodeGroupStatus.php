<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Lifecycle status of a Kubernetes node group.
 */
enum KubernetesNodeGroupStatus: string
{
    case Pending = 'PENDING';
    case Created = 'CREATED';
    case ToResume = 'TO_RESUME';
    case Deploying = 'DEPLOYING';
    case Deployed = 'DEPLOYED';
    case ToResize = 'TO_RESIZE';
    case Resizing = 'RESIZING';
    case Ready = 'READY';
    case Failed = 'FAILED';
    case ToTerminate = 'TO_TERMINATE';
    case Terminating = 'TERMINATING';
    case Deleted = 'DELETED';
}
