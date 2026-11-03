<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Effect of a Kubernetes taint on pod scheduling.
 */
enum KubernetesTaintEffect: string
{
    case NoSchedule = 'NoSchedule';
    case PreferNoSchedule = 'PreferNoSchedule';
    case NoExecute = 'NoExecute';
}
