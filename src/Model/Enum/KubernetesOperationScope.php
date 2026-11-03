<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Which part of a cluster a version update targets.
 */
enum KubernetesOperationScope: string
{
    case All = 'ALL';
    case ControlPlane = 'CONTROL_PLANE';
    case Workers = 'WORKERS';
}
