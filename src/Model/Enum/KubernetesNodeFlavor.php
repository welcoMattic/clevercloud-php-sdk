<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Size of a Kubernetes node (worker or control plane).
 */
enum KubernetesNodeFlavor: string
{
    case TwoXS = '2XS';
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
}
