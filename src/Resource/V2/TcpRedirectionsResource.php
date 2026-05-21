<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\TcpRedirection;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Manage TCP port redirections for an application — used when an app needs to
 * expose a raw TCP socket (PostgreSQL replication, custom protocols) rather
 * than HTTP.
 *
 * Routes follow the V2 application scope:
 * `/organisations/{ownerId}/applications/{appId}/tcp-redirs[/{port}/{namespace}]`.
 * A separate `/tcp-redirs/namespaces` endpoint lists the namespaces that the
 * owner is authorised to bind ports against.
 */
final readonly class TcpRedirectionsResource extends AbstractV2Resource
{
    /**
     * @return list<TcpRedirection>
     */
    public function list(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($applicationId, $organisationId));

        return $this->mapCollection(TcpRedirection::class, $payload);
    }

    /**
     * Lists namespaces the current scope can bind TCP ports against.
     *
     * @return list<string>
     */
    public function namespaces(?string $organisationId = null): array
    {
        /** @var array<int|string, mixed> $payload */
        $payload = $this->httpGet($this->ownerPath($organisationId).'/tcp-redirs/namespaces');

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

    public function remove(string $applicationId, int $port, string $namespace, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($applicationId, $organisationId).'/'.$port.'?namespace='.rawurlencode($namespace),
        );
    }

    private function basePath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/tcp-redirs';
    }
}
