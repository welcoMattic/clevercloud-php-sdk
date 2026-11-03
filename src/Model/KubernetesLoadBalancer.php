<?php

namespace CleverCloud\Sdk\Model;

/**
 * A load balancer in front of a Kubernetes cluster API server.
 */
final readonly class KubernetesLoadBalancer
{
    /**
     * @param list<string> $ips
     */
    public function __construct(
        public string $id,
        public string $flavor,
        public string $domainName,
        public array $ips = [],
    ) {
    }
}
