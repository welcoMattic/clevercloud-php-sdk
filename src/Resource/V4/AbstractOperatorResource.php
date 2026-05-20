<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Operator;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Shared CRUD + lifecycle base class for the four operator add-ons exposed
 * under `/v4/addon-providers/addon-{kind}/addons[/{id}]`. Subclasses declare
 * their operator slug. `$organisationId` is not part of the URL — the API
 * resolves ownership from the caller's credentials.
 */
abstract readonly class AbstractOperatorResource extends AbstractV4Resource
{
    abstract protected function operator(): string;

    /**
     * @return list<Operator>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath());

        return $this->mapCollection(Operator::class, $payload);
    }

    public function get(string $id): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath().'/'.rawurlencode($id));

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, region, planId, ...}
     */
    public function create(array $data): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath(), ['json' => $data]);

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->basePath().'/'.rawurlencode($id),
            ['json' => $data],
        );

        return $this->mapTo(Operator::class, $payload);
    }

    public function delete(string $id): void
    {
        $this->httpDelete($this->basePath().'/'.rawurlencode($id));
    }

    public function reboot(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/reboot');
    }

    public function rebuild(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/rebuild');
    }

    /**
     * Links the operator to a Network Group (Keycloak / Otoroshi only).
     */
    public function linkNetworkGroup(string $id): void
    {
        $this->httpPost($this->basePath().'/'.rawurlencode($id).'/networkgroup');
    }

    public function unlinkNetworkGroup(string $id): void
    {
        $this->httpDelete($this->basePath().'/'.rawurlencode($id).'/networkgroup');
    }

    private function basePath(): string
    {
        return '/addon-providers/addon-'.$this->operator().'/addons';
    }
}
