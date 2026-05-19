<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Application;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class ApplicationsResource extends AbstractV2Resource
{
    /**
     * @return list<Application>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->ownerPath($organisationId).'/applications');

        return $this->mapCollection(Application::class, $payload);
    }

    public function get(string $applicationId, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId),
        );

        return $this->mapTo(Application::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, deploy: 'git'|'ftp', instanceType, instanceVariant, zone}
     */
    public function create(array $data, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->ownerPath($organisationId).'/applications',
            ['json' => $data],
        );

        return $this->mapTo(Application::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $applicationId, array $data, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId),
            ['json' => $data],
        );

        return $this->mapTo(Application::class, $payload);
    }

    public function delete(string $applicationId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId),
        );
    }

    public function restart(string $applicationId, ?string $organisationId = null, bool $withoutCache = false): void
    {
        $query = $withoutCache ? ['useCache' => 'no'] : [];
        $this->httpPost(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/instances',
            ['query' => $query],
        );
    }

    public function stop(string $applicationId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/instances',
        );
    }

    public function setBranch(string $applicationId, string $branch, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/branch',
            ['json' => ['branch' => $branch]],
        );
    }

    /**
     * Lists currently running instances for an application.
     *
     * @return list<array<string, mixed>>
     */
    public function instances(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/instances',
        );

        return $payload;
    }
}
