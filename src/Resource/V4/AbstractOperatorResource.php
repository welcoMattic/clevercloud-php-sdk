<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Operator;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Shared CRUD + lifecycle base class for the four operator add-ons exposed
 * under `/v4/operators/{kind}`. Subclasses just declare their operator slug.
 */
abstract readonly class AbstractOperatorResource extends AbstractV4Resource
{
    abstract protected function operator(): string;

    /**
     * @return list<Operator>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(Operator::class, $payload);
    }

    public function get(string $id, ?string $organisationId = null): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($organisationId).'/'.rawurlencode($id));

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, region, planId, ...}
     */
    public function create(array $data, ?string $organisationId = null): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $data]);

        return $this->mapTo(Operator::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data, ?string $organisationId = null): Operator
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->basePath($organisationId).'/'.rawurlencode($id),
            ['json' => $data],
        );

        return $this->mapTo(Operator::class, $payload);
    }

    public function delete(string $id, ?string $organisationId = null): void
    {
        $this->httpDelete($this->basePath($organisationId).'/'.rawurlencode($id));
    }

    public function reboot(string $id, ?string $organisationId = null): void
    {
        $this->httpPost($this->basePath($organisationId).'/'.rawurlencode($id).'/reboot');
    }

    public function rebuild(string $id, ?string $organisationId = null): void
    {
        $this->httpPost($this->basePath($organisationId).'/'.rawurlencode($id).'/rebuild');
    }

    private function basePath(?string $organisationId): string
    {
        return '/operators/'.$this->operator().$this->ownerPath($organisationId);
    }
}
