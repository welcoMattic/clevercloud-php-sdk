<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Operation a Kubernetes deployment event belongs to.
 */
enum KubernetesDeploymentEventOperation: string
{
    case Create = 'CREATE';
    case Redeploy = 'REDEPLOY';
    case Upgrade = 'UPGRADE';
    case Delete = 'DELETE';
    case NodeGroupCreate = 'NODEGROUP_CREATE';
    case NodeGroupDelete = 'NODEGROUP_DELETE';
    case NodeGroupScale = 'NODEGROUP_SCALE';
    case NodeGroupResume = 'NODEGROUP_RESUME';
    case NodeGroupRedeploy = 'NODEGROUP_REDEPLOY';
}
