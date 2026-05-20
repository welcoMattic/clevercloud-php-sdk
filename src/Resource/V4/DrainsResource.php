<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Drain;
use CleverCloud\Sdk\Model\Enum\DrainType;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages log drains under
 * `/v4/drains/organisations/{ownerId}/resources/{resourceId}/drains`.
 *
 * `$resourceId` is an application id (`app_…`) or an add-on real id
 * (`postgresql_…`, `cellar_…`, …) — the API treats anything that produces
 * logs as a drainable "resource".
 */
final readonly class DrainsResource extends AbstractV4Resource
{
    /**
     * @return list<Drain>
     */
    public function list(string $resourceId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($resourceId, $organisationId));

        return $this->mapCollection(Drain::class, $payload);
    }

    public function get(string $resourceId, string $drainId, ?string $organisationId = null): Drain
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId),
        );

        return $this->mapTo(Drain::class, $payload);
    }

    /**
     * @param array<string, mixed> $credentials extra fields per drain kind (API key for Datadog/NewRelic, basic auth, etc.)
     */
    public function create(
        string $resourceId,
        DrainType $kind,
        string $url,
        array $credentials = [],
        ?string $organisationId = null,
    ): Drain {
        $body = ['kind' => $kind->value, 'url' => $url];
        if ([] !== $credentials) {
            $body['credentials'] = $credentials;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->basePath($resourceId, $organisationId),
            ['json' => $body],
        );

        return $this->mapTo(Drain::class, $payload);
    }

    public function delete(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId),
        );
    }

    public function enable(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId).'/state',
            ['json' => ['enabled' => true]],
        );
    }

    public function disable(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId).'/state',
            ['json' => ['enabled' => false]],
        );
    }

    /**
     * Restarts log forwarding from the latest position. Useful after a drain
     * has fallen behind and you want to skip the backlog.
     */
    public function restart(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPatch(
            $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId),
            ['json' => ['action' => 'restart']],
        );
    }

    private function basePath(string $resourceId, ?string $organisationId): string
    {
        return '/drains'.$this->ownerPath($organisationId)
            .'/resources/'.rawurlencode($resourceId).'/drains';
    }
}
