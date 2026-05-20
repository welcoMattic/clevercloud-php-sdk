<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Deployment;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads runtime orchestration data (live instance state, recent deployments)
 * under `/v4/orchestration/organisations/{ownerId}/applications/{applicationId}/{instances|deployments}`.
 *
 * The v2 equivalents (`ApplicationsResource::instances`, `DeploymentsResource`)
 * still work; v4 returns richer payloads (orchestration metadata, more accurate
 * timing).
 */
final readonly class OrchestrationResource extends AbstractV4Resource
{
    /**
     * @return list<array<string, mixed>>
     */
    public function instances(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($applicationId, $organisationId).'/instances');

        return $payload;
    }

    /**
     * @return list<Deployment>
     */
    public function deployments(string $applicationId, ?string $organisationId = null, ?int $limit = null, ?int $offset = null): array
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
            $this->basePath($applicationId, $organisationId).'/deployments',
            ['query' => $query],
        );

        return $this->mapCollection(Deployment::class, $payload);
    }

    public function getDeployment(string $applicationId, string $deploymentId, ?string $organisationId = null): Deployment
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->basePath($applicationId, $organisationId).'/deployments/'.rawurlencode($deploymentId),
        );

        return $this->mapTo(Deployment::class, $payload);
    }

    private function basePath(string $applicationId, ?string $organisationId): string
    {
        return '/orchestration'.$this->ownerPath($organisationId)
            .'/applications/'.rawurlencode($applicationId);
    }
}
