<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\LoadBalancer;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads the load balancers serving an application under
 * `/v4/load-balancers/organisations/{ownerId}/applications/{applicationId}/load-balancers/{kind}`.
 */
final readonly class LoadBalancersResource extends AbstractV4Resource
{
    /**
     * Lists the load balancers of a kind serving an application, one per
     * zone, each with the DNS records (`cname`, `a`) to point a domain at it.
     *
     * @param string $kind `default` (public load balancers) or `private`
     *
     * @return list<LoadBalancer>
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function list(string $applicationId, ?string $organisationId = null, string $kind = 'default'): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            '/load-balancers'.$this->ownerPath($organisationId)
            .'/applications/'.rawurlencode($applicationId).'/load-balancers/'.rawurlencode($kind),
        );

        return $this->mapCollection(LoadBalancer::class, $payload);
    }
}
