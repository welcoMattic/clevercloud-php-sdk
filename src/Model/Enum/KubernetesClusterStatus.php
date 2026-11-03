<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Lifecycle status of a Kubernetes cluster.
 */
enum KubernetesClusterStatus: string
{
    case ToDeploy = 'TO_DEPLOY';
    case Deploying = 'DEPLOYING';
    case Active = 'ACTIVE';
    case ToRedeploy = 'TO_REDEPLOY';
    case Redeploying = 'REDEPLOYING';
    case ToUpgrade = 'TO_UPGRADE';
    case Updating = 'UPDATING';
    case ToReconcile = 'TO_RECONCILE';
    case Reconciling = 'RECONCILING';
    case WaitingOnDependency = 'WAITING_ON_DEPENDENCY';
    case ToDelete = 'TO_DELETE';
    case Deleting = 'DELETING';
    case Deleted = 'DELETED';
    case Failed = 'FAILED';
}
