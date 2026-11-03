<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\TcpRedirection;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Manage TCP port redirections for an application, used when an app needs to
 * expose a raw TCP socket (PostgreSQL replication, custom protocols) rather
 * than HTTP.
 *
 * Routes: `/v2/organisations/{ownerId}/applications/{appId}/tcpRedirs[/{port}]`
 * and `/v2/organisations/{ownerId}/namespaces` for the namespaces the owner can
 * bind ports against. There is no `/self` alias: a `null` `$organisationId`
 * resolves the personal organisation id through `GET /v2/self` first.
 */
final readonly class TcpRedirectionsResource extends AbstractV2Resource
{
    /**
     * Lists the TCP redirections of an application.
     *
     * @return list<TcpRedirection>
     */
    public function list(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($applicationId, $organisationId));

        return $this->mapCollection(TcpRedirection::class, $payload);
    }

    /**
     * Lists namespaces the owner can bind TCP ports against.
     *
     * @return list<string>
     */
    public function namespaces(?string $organisationId = null): array
    {
        /** @var array<int|string, mixed> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($this->resolveOwnerId($organisationId)).'/namespaces');

        $names = [];
        foreach ($payload as $value) {
            if (\is_string($value)) {
                $names[] = $value;
            } elseif (\is_array($value) && isset($value['namespace']) && \is_string($value['namespace'])) {
                $names[] = $value['namespace'];
            }
        }

        return $names;
    }

    /**
     * Allocates a TCP port on the given namespace for the application.
     * Clever Cloud picks the actual port number; it's returned on the result.
     */
    public function add(string $applicationId, string $namespace, ?string $organisationId = null): TcpRedirection
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->basePath($applicationId, $organisationId),
            ['json' => ['namespace' => $namespace]],
        );

        return $this->mapTo(TcpRedirection::class, $payload);
    }

    /**
     * Releases a TCP port of the application on the given namespace.
     */
    public function remove(string $applicationId, int $port, string $namespace, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($applicationId, $organisationId).'/'.$port.'?namespace='.rawurlencode($namespace),
        );
    }

    private function basePath(string $applicationId, ?string $organisationId): string
    {
        return '/organisations/'.rawurlencode($this->resolveOwnerId($organisationId))
            .'/applications/'.rawurlencode($applicationId).'/tcpRedirs';
    }
}
