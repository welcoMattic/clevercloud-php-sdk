<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Vhost;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class DomainsResource extends AbstractV2Resource
{
    /**
     * @return list<Vhost>
     */
    public function list(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->vhostsPath($applicationId, $organisationId));

        return $this->mapCollection(Vhost::class, $payload);
    }

    public function add(string $applicationId, string $fqdn, ?string $organisationId = null): void
    {
        $this->httpPost(
            $this->vhostsPath($applicationId, $organisationId).'/'.rawurlencode($fqdn),
        );
    }

    public function remove(string $applicationId, string $fqdn, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->vhostsPath($applicationId, $organisationId).'/'.rawurlencode($fqdn),
        );
    }

    public function favourite(string $applicationId, ?string $organisationId = null): ?Vhost
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->vhostsPath($applicationId, $organisationId).'/favourite');

        if ([] === $payload) {
            return null;
        }

        return $this->mapTo(Vhost::class, $payload);
    }

    public function setFavourite(string $applicationId, string $fqdn, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->vhostsPath($applicationId, $organisationId).'/favourite',
            ['json' => ['fqdn' => $fqdn]],
        );
    }

    public function unsetFavourite(string $applicationId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->vhostsPath($applicationId, $organisationId).'/favourite');
    }

    private function vhostsPath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/vhosts';
    }
}
