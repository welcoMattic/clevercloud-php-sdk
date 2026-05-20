<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\NetworkGroup;
use CleverCloud\Sdk\Model\NetworkGroupMember;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages WireGuard Network Groups under
 * `/v4/networkgroups/organisations/{ownerId}/networkgroups`.
 */
final readonly class NetworkGroupsResource extends AbstractV4Resource
{
    /**
     * @return list<NetworkGroup>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId));

        return $this->mapCollection(NetworkGroup::class, $payload);
    }

    public function get(string $networkGroupId, ?string $organisationId = null): NetworkGroup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($organisationId).'/'.rawurlencode($networkGroupId));

        return $this->mapTo(NetworkGroup::class, $payload);
    }

    public function create(string $label, ?string $description = null, ?string $organisationId = null): NetworkGroup
    {
        $body = ['label' => $label];
        if (null !== $description) {
            $body['description'] = $description;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $body]);

        return $this->mapTo(NetworkGroup::class, $payload);
    }

    public function delete(string $networkGroupId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->basePath($organisationId).'/'.rawurlencode($networkGroupId));
    }

    /**
     * @return list<NetworkGroupMember>
     */
    public function members(string $networkGroupId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->basePath($organisationId).'/'.rawurlencode($networkGroupId).'/members',
        );

        return $this->mapCollection(NetworkGroupMember::class, $payload);
    }

    public function addMember(
        string $networkGroupId,
        string $memberId,
        string $kind,
        ?string $label = null,
        ?string $organisationId = null,
    ): void {
        $body = ['id' => $memberId, 'kind' => $kind];
        if (null !== $label) {
            $body['label'] = $label;
        }
        $this->httpPost(
            $this->basePath($organisationId).'/'.rawurlencode($networkGroupId).'/members',
            ['json' => $body],
        );
    }

    public function removeMember(string $networkGroupId, string $memberId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->basePath($organisationId).'/'.rawurlencode($networkGroupId)
                .'/members/'.rawurlencode($memberId),
        );
    }

    /**
     * Returns the WireGuard configuration for an external peer to join the
     * Network Group.
     */
    public function externalPeerConfig(
        string $networkGroupId,
        string $peerId,
        ?string $organisationId = null,
    ): string {
        $response = $this->httpStream(
            'GET',
            $this->basePath($organisationId).'/'.rawurlencode($networkGroupId)
                .'/external-peers/'.rawurlencode($peerId).'/config',
        );

        return (string) $response->getBody();
    }

    private function basePath(?string $organisationId): string
    {
        return '/networkgroups'.$this->ownerPath($organisationId).'/networkgroups';
    }
}
