<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Kubeconfig variant: how `kubectl` reaches the API server.
 */
enum KubernetesKubeconfigType: string
{
    case Loadbalancer = 'LOADBALANCER';
    case NetworkGroup = 'NETWORKGROUP';
    case ApiServerIp = 'APISERVER_IP';
}
