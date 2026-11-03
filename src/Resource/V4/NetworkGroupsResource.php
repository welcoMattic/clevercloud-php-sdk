<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\NetworkGroup;
use CleverCloud\Sdk\Model\NetworkGroupMember;
use CleverCloud\Sdk\Model\NetworkGroupPeer;
use CleverCloud\Sdk\Model\NetworkGroupsConfiguration;
use CleverCloud\Sdk\Model\StreamEvent;
use CleverCloud\Sdk\Resource\AbstractV4Resource;
use CleverCloud\Sdk\Streaming\EventStream;
use CleverCloud\Sdk\Streaming\NetworkGroupStream;

/**
 * Manages WireGuard Network Groups under
 * `/v4/networkgroups/organisations/{ownerId}/networkgroups`.
 */
final readonly class NetworkGroupsResource extends AbstractV4Resource
{
    /**
     * Returns the platform-wide Network Group settings, such as the DNS
     * suffixes a member `domainName` may end with.
     *
     * Served at the API root (`/networkgroup/configuration`), outside `/v4`.
     */
    public function configuration(): NetworkGroupsConfiguration
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->http->request('GET', ApiVersion::Root, '/networkgroup/configuration');

        return $this->mapTo(NetworkGroupsConfiguration::class, $payload);
    }

    /**
     * Lists the Network Groups of the owner.
     *
     * @param list<string> $tags keep only the groups that carry every given tag
     *
     * @return list<NetworkGroup>
     */
    public function list(?string $organisationId = null, array $tags = []): array
    {
        $options = [] === $tags ? [] : ['query' => ['tags' => $tags]];

        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId), $options);

        return $this->mapCollection(NetworkGroup::class, $payload);
    }

    /**
     * Case-insensitive substring search over the IDs, labels and tags of the
     * owner's Network Groups and the IDs and labels of their members and peers.
     *
     * Hits are returned as one flat list, each one typed after its `type`
     * discriminator. Hits of an unknown type are skipped.
     *
     * @return list<NetworkGroup|NetworkGroupMember|NetworkGroupPeer>
     */
    public function search(string $query, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId).'/search', ['query' => ['query' => $query]]);

        $hits = [];
        foreach ($payload as $hit) {
            $class = match ($hit['type'] ?? null) {
                'NetworkGroup' => NetworkGroup::class,
                'Member' => NetworkGroupMember::class,
                'CleverPeer', 'ExternalPeer' => NetworkGroupPeer::class,
                default => null,
            };
            if (null !== $class) {
                $hits[] = $this->mapTo($class, $hit);
            }
        }

        return $hits;
    }

    public function get(string $networkGroupId, ?string $organisationId = null): NetworkGroup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->groupPath($networkGroupId, $organisationId));

        return $this->mapTo(NetworkGroup::class, $payload);
    }

    /**
     * Requests the creation of a Network Group.
     *
     * The API answers `202 Accepted` with an empty body: the group is created
     * asynchronously and cannot be read until it is. Pass `$id` to know its ID
     * up front.
     *
     * @param list<string>                                                               $tags
     * @param list<array{id: string, kind: string, label?: string, domainName?: string}> $members initial members
     */
    public function create(
        string $label,
        ?string $description = null,
        ?string $organisationId = null,
        ?string $id = null,
        array $tags = [],
        array $members = [],
    ): void {
        $body = ['label' => $label];
        if (null !== $description) {
            $body['description'] = $description;
        }
        if (null !== $id) {
            $body['id'] = $id;
        }
        if ([] !== $tags) {
            $body['tags'] = $tags;
        }
        if ([] !== $members) {
            $body['members'] = $members;
        }

        $this->httpPost($this->basePath($organisationId), ['json' => $body]);
    }

    /**
     * Requests the deletion of a Network Group, applied asynchronously.
     */
    public function delete(string $networkGroupId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->groupPath($networkGroupId, $organisationId));
    }

    /**
     * Opens a Server-Sent Events stream that yields the Network Group every
     * time its version changes. The stream ends when the group disappears.
     *
     * @param int|null $maxDurationSeconds stop iterating after this many seconds; null tails until the group disappears
     */
    public function stream(
        string $networkGroupId,
        ?string $organisationId = null,
        ?int $maxDurationSeconds = null,
    ): NetworkGroupStream {
        $handle = $this->httpEventStream($this->groupPath($networkGroupId, $organisationId).'/stream');

        return new NetworkGroupStream(new EventStream($handle, $maxDurationSeconds), $this->mapper);
    }

    /**
     * @return list<NetworkGroupMember>
     */
    public function members(string $networkGroupId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->groupPath($networkGroupId, $organisationId).'/members');

        return $this->mapCollection(NetworkGroupMember::class, $payload);
    }

    public function member(string $networkGroupId, string $memberId, ?string $organisationId = null): NetworkGroupMember
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->groupPath($networkGroupId, $organisationId).'/members/'.rawurlencode($memberId),
        );

        return $this->mapTo(NetworkGroupMember::class, $payload);
    }

    /**
     * Requests the addition of a member, applied asynchronously.
     *
     * @param string      $kind       `APPLICATION`, `ADDON`, `EXTERNAL` or `LOADBALANCER`, in any case
     * @param string|null $domainName must end with one of the {@see configuration()} DNS suffixes; derived when null
     */
    public function addMember(
        string $networkGroupId,
        string $memberId,
        string $kind,
        ?string $label = null,
        ?string $organisationId = null,
        ?string $domainName = null,
    ): void {
        $body = ['id' => $memberId, 'kind' => $kind];
        if (null !== $label) {
            $body['label'] = $label;
        }
        if (null !== $domainName) {
            $body['domainName'] = $domainName;
        }
        $this->httpPost($this->groupPath($networkGroupId, $organisationId).'/members', ['json' => $body]);
    }

    /**
     * Requests the removal of a member, applied asynchronously.
     */
    public function removeMember(string $networkGroupId, string $memberId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->groupPath($networkGroupId, $organisationId).'/members/'.rawurlencode($memberId),
        );
    }

    /**
     * Lists the WireGuard peers of a Network Group, Clever Cloud instances and
     * external peers alike.
     *
     * @return list<NetworkGroupPeer>
     */
    public function peers(string $networkGroupId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->groupPath($networkGroupId, $organisationId).'/peers');

        return $this->mapCollection(NetworkGroupPeer::class, $payload);
    }

    public function peer(string $networkGroupId, string $peerId, ?string $organisationId = null): NetworkGroupPeer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->peerPath($networkGroupId, $peerId, $organisationId));

        return $this->mapTo(NetworkGroupPeer::class, $payload);
    }

    /**
     * Requests the addition of a WireGuard peer under the member
     * `$parentMember`. The peer is created asynchronously.
     *
     * A `SERVER` peer needs `$ip` and `$port`, an `EXTERNAL` peer its
     * `$publicKey`, a `CLEVER` peer its hypervisor `$hv`.
     *
     * @param string $peerRole `CLIENT` or `SERVER`
     * @param string $peerKind `CLEVER` or `EXTERNAL`
     *
     * @return string the ID of the created peer
     */
    public function addPeer(
        string $networkGroupId,
        string $peerId,
        string $peerRole,
        string $peerKind,
        string $parentMember,
        ?string $label = null,
        ?string $publicKey = null,
        ?string $hostname = null,
        ?string $ip = null,
        ?int $port = null,
        ?string $hv = null,
        ?string $parentEvent = null,
        ?string $organisationId = null,
    ): string {
        $body = array_filter([
            'id' => $peerId,
            'peerRole' => $peerRole,
            'peerKind' => $peerKind,
            'parentMember' => $parentMember,
            'label' => $label,
            'publicKey' => $publicKey,
            'hostname' => $hostname,
            'ip' => $ip,
            'port' => $port,
            'hv' => $hv,
            'parentEvent' => $parentEvent,
        ], static fn (string|int|null $value): bool => null !== $value);

        return $this->createdPeerId(
            $this->httpPost($this->groupPath($networkGroupId, $organisationId).'/peers', ['json' => $body]),
        );
    }

    /**
     * Requests the removal of an external peer, applied asynchronously. A
     * Clever Cloud instance peer cannot be removed this way: it leaves the
     * group with its instance or its member.
     */
    public function removePeer(string $networkGroupId, string $peerId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->peerPath($networkGroupId, $peerId, $organisationId));
    }

    /**
     * Requests the addition of a WireGuard peer running outside Clever Cloud
     * under the member `$parentMember`. The peer is created asynchronously.
     *
     * @param string $peerRole  `CLIENT` or `SERVER`; a `SERVER` peer needs `$ip` and `$port`
     * @param string $publicKey WireGuard public key, base64-encoded
     *
     * @return string the generated ID of the peer
     */
    public function addExternalPeer(
        string $networkGroupId,
        string $label,
        string $peerRole,
        string $publicKey,
        string $parentMember,
        ?string $hostname = null,
        ?string $ip = null,
        ?int $port = null,
        ?string $parentEvent = null,
        ?string $organisationId = null,
    ): string {
        $body = array_filter([
            'label' => $label,
            'peerRole' => $peerRole,
            'publicKey' => $publicKey,
            'parentMember' => $parentMember,
            'hostname' => $hostname,
            'ip' => $ip,
            'port' => $port,
            'parentEvent' => $parentEvent,
        ], static fn (string|int|null $value): bool => null !== $value);

        return $this->createdPeerId(
            $this->httpPost($this->groupPath($networkGroupId, $organisationId).'/external-peers', ['json' => $body]),
        );
    }

    public function externalPeer(string $networkGroupId, string $peerId, ?string $organisationId = null): NetworkGroupPeer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->externalPeerPath($networkGroupId, $peerId, $organisationId));

        return $this->mapTo(NetworkGroupPeer::class, $payload);
    }

    /**
     * Requests the removal of an external peer, applied asynchronously.
     */
    public function removeExternalPeer(string $networkGroupId, string $peerId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->externalPeerPath($networkGroupId, $peerId, $organisationId));
    }

    /**
     * Returns the WireGuard configuration of a peer as INI text: its
     * `[Interface]` section and one `[Peer]` section per other peer.
     */
    public function wireguardConfiguration(string $networkGroupId, string $peerId, ?string $organisationId = null): string
    {
        $response = $this->httpStream(
            'GET',
            $this->peerPath($networkGroupId, $peerId, $organisationId).'/wireguard/configuration',
            ['headers' => ['Accept' => 'text/plain']],
        );

        return (string) $response->getBody();
    }

    /**
     * Returns a URL that downloads the WireGuard configuration of a peer with
     * no other credential, valid 60 minutes. Keep it secret: its token grants
     * that download for every peer of the organisation's Network Groups until
     * it expires. Not available to biscuit credentials.
     */
    public function wireguardConfigurationPresignedUrl(
        string $networkGroupId,
        string $peerId,
        ?string $organisationId = null,
    ): string {
        $payload = $this->httpGet(
            $this->peerPath($networkGroupId, $peerId, $organisationId).'/wireguard/configuration/presigned-url',
        );

        if (!isset($payload['url']) || !\is_string($payload['url'])) {
            throw new ApiException('Presigned URL response carries no "url"', 0);
        }

        return $payload['url'];
    }

    /**
     * Opens a Server-Sent Events stream that yields the WireGuard
     * configuration of a peer every time the Network Group version changes.
     * The stream ends when the group or the peer disappears.
     *
     * Each event is yielded as a {@see StreamEvent} whose `data` is the
     * decoded JSON object. The API documents it as `WireGuardConfigurationB64`
     * (the INI text, base64-encoded) without publishing its fields, so the SDK
     * does not pick one.
     *
     * @param int|null $maxDurationSeconds stop iterating after this many seconds; null tails until the peer disappears
     */
    public function streamWireguardConfiguration(
        string $networkGroupId,
        string $peerId,
        ?string $organisationId = null,
        ?int $maxDurationSeconds = null,
    ): EventStream {
        $handle = $this->httpEventStream(
            $this->peerPath($networkGroupId, $peerId, $organisationId).'/wireguard/configuration/stream',
        );

        return new EventStream($handle, $maxDurationSeconds);
    }

    /**
     * Returns the WireGuard configuration for an external peer to join the
     * Network Group.
     *
     * The API no longer serves `.../external-peers/{peerId}/config`, so this
     * method now reads `.../peers/{peerId}/wireguard/configuration`.
     *
     * @deprecated since 2.1, use wireguardConfiguration()
     */
    public function externalPeerConfig(
        string $networkGroupId,
        string $peerId,
        ?string $organisationId = null,
    ): string {
        return $this->wireguardConfiguration($networkGroupId, $peerId, $organisationId);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function createdPeerId(array $payload): string
    {
        if (!isset($payload['peerId']) || !\is_string($payload['peerId'])) {
            throw new ApiException('Peer creation response carries no "peerId"', 0);
        }

        return $payload['peerId'];
    }

    private function basePath(?string $organisationId): string
    {
        return '/networkgroups'.$this->ownerPath($organisationId).'/networkgroups';
    }

    private function groupPath(string $networkGroupId, ?string $organisationId): string
    {
        return $this->basePath($organisationId).'/'.rawurlencode($networkGroupId);
    }

    private function peerPath(string $networkGroupId, string $peerId, ?string $organisationId): string
    {
        return $this->groupPath($networkGroupId, $organisationId).'/peers/'.rawurlencode($peerId);
    }

    private function externalPeerPath(string $networkGroupId, string $peerId, ?string $organisationId): string
    {
        return $this->groupPath($networkGroupId, $organisationId).'/external-peers/'.rawurlencode($peerId);
    }
}
