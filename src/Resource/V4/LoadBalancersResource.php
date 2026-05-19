<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\LoadBalancer;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

final readonly class LoadBalancersResource extends AbstractV4Resource
{
    /**
     * @return list<LoadBalancer>
     */
    public function list(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($applicationId, $organisationId));

        return $this->mapCollection(LoadBalancer::class, $payload);
    }

    public function get(string $applicationId, string $loadBalancerId, ?string $organisationId = null): LoadBalancer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->basePath($applicationId, $organisationId).'/'.rawurlencode($loadBalancerId),
        );

        return $this->mapTo(LoadBalancer::class, $payload);
    }

    /**
     * Returns the DNS records (A / AAAA / CNAME) that should point at the load
     * balancer for a given application.
     *
     * @return array<string, mixed>
     */
    public function dnsInfo(string $applicationId, ?string $organisationId = null): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->basePath($applicationId, $organisationId).'/default/dns',
        );

        return $payload;
    }

    private function basePath(string $applicationId, ?string $organisationId): string
    {
        return '/load-balancers'.$this->ownerPath($organisationId)
            .'/applications/'.rawurlencode($applicationId).'/load-balancers';
    }
}
