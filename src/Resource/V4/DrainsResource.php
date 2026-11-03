<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\Drain;
use CleverCloud\Sdk\Model\Enum\DrainKind;
use CleverCloud\Sdk\Model\Enum\DrainType;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages log drains under
 * `/v4/drains/organisations/{ownerId}/resources/{resourceId}/drains`.
 *
 * `$resourceId` is an application id (`app_...`) or an add-on real id
 * (`postgresql_...`, `cellar_...`, ...): the API treats anything that
 * produces logs as a drainable "resource". A null `$organisationId` targets
 * the personal space, resolved through `GET /v2/self`.
 */
final readonly class DrainsResource extends AbstractV4Resource
{
    /**
     * @return list<Drain>
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function list(string $resourceId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($resourceId, $organisationId));

        return $this->mapCollection(Drain::class, $payload);
    }

    /**
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function get(string $resourceId, string $drainId, ?string $organisationId = null): Drain
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->drainPath($resourceId, $drainId, $organisationId));

        return $this->mapTo(Drain::class, $payload);
    }

    /**
     * Creates a log drain. The drain is enabled asynchronously: poll
     * {@see self::get()} until its `status.status` is `ENABLED`.
     *
     * @param array<string, mixed> $recipient extra recipient fields of the protocol: `username` / `password` (raw HTTP, ElasticSearch), `apiKey` (NewRelic), `sourceToken` (Better Stack), `token` (OVH TCP, Splunk), `index`, `sourcetype`, `tlsVerification`, `rfc5424StructuredDataParameters`
     * @param bool                 $skipCheck create the drain without probing the target first; by default the API refuses a target it cannot reach
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function create(
        string $resourceId,
        DrainType $type,
        string $url,
        array $recipient = [],
        ?string $organisationId = null,
        DrainKind $kind = DrainKind::Log,
        bool $skipCheck = false,
    ): Drain {
        $options = ['json' => [
            'kind' => $kind->value,
            'recipient' => ['type' => $type->value, 'url' => $url] + $recipient,
        ]];
        if ($skipCheck) {
            $options['query'] = ['skipCheck' => 'true'];
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($resourceId, $organisationId), $options);

        return $this->mapTo(Drain::class, $payload);
    }

    /**
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function delete(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->drainPath($resourceId, $drainId, $organisationId));
    }

    /**
     * Requests the enabling of a disabled drain, applied asynchronously.
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function enable(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPut($this->drainPath($resourceId, $drainId, $organisationId).'/enable');
    }

    /**
     * Requests the disabling of a drain, applied asynchronously.
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function disable(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPut($this->drainPath($resourceId, $drainId, $organisationId).'/disable');
    }

    /**
     * Moves the shipping cursor of a drain to now, so it skips its backlog and
     * only ships the logs produced from this point on.
     *
     * @throws ApiException when `$organisationId` is null and the personal organisation id cannot be resolved
     */
    public function restart(string $resourceId, string $drainId, ?string $organisationId = null): void
    {
        $this->httpPatch($this->drainPath($resourceId, $drainId, $organisationId).'/reset-cursor');
    }

    private function basePath(string $resourceId, ?string $organisationId): string
    {
        return '/drains'.$this->ownerPath($organisationId)
            .'/resources/'.rawurlencode($resourceId).'/drains';
    }

    private function drainPath(string $resourceId, string $drainId, ?string $organisationId): string
    {
        return $this->basePath($resourceId, $organisationId).'/'.rawurlencode($drainId);
    }
}
