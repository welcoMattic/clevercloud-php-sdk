<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Deployment;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class DeploymentsResource extends AbstractV2Resource
{
    /**
     * @return list<Deployment>
     */
    public function list(string $applicationId, ?string $organisationId = null, ?int $limit = null, ?int $offset = null): array
    {
        $query = [];
        if (null !== $limit) {
            $query['limit'] = $limit;
        }
        if (null !== $offset) {
            $query['offset'] = $offset;
        }

        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->deploymentsPath($applicationId, $organisationId),
            ['query' => $query],
        );

        return $this->mapCollection(Deployment::class, $payload);
    }

    public function get(string $applicationId, string $deploymentId, ?string $organisationId = null): Deployment
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->deploymentsPath($applicationId, $organisationId).'/'.rawurlencode($deploymentId),
        );

        return $this->mapTo(Deployment::class, $payload);
    }

    public function cancel(string $applicationId, string $deploymentId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->deploymentsPath($applicationId, $organisationId).'/'.rawurlencode($deploymentId),
        );
    }

    private function deploymentsPath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/deployments';
    }
}
