<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Model\NetworkGroup;
use CleverCloud\Sdk\Model\NetworkGroupMember;
use CleverCloud\Sdk\Model\NetworkGroupPeer;
use CleverCloud\Sdk\Resource\V4\NetworkGroupsResource;
use CleverCloud\Sdk\Streaming\NetworkGroupStream;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(NetworkGroupsResource::class)]
#[CoversClass(NetworkGroupStream::class)]
final class NetworkGroupsResourceTest extends TestCase
{
    private const string BASE = 'https://api.clever-cloud.com/v4/networkgroups/organisations/orga_1/networkgroups';

    public function testConfigurationHitsTheApiRoot(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['dnsSuffixes' => ['ng.clever-cloud.com']]);

        $configuration = $this->resource($response)->configuration();

        self::assertSame(['ng.clever-cloud.com'], $configuration->dnsSuffixes);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/networkgroup/configuration', $response->getRequestUrl());
    }

    public function testListReturnsTypedGroupsWithMembersAndPeers(): void
    {
        $response = ResourceFactory::jsonResponse(200, [self::networkGroupPayload()]);

        $groups = $this->resource($response)->list('orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE, $response->getRequestUrl());
        self::assertCount(1, $groups);

        $group = $groups[0];
        self::assertSame('ng_1', $group->id);
        self::assertSame('my-ng', $group->label);
        self::assertSame('my_ng', $group->dnsSanitizedLabel);
        self::assertSame('orga_1', $group->ownerId);
        self::assertSame('10.101.0.0/16', $group->networkIp);
        self::assertSame('10.101.0.3', $group->lastAllocatedIp);
        self::assertSame('customer', $group->ownership);
        self::assertSame(['prod'], $group->tags);
        self::assertSame(7, $group->version);

        self::assertCount(1, $group->members);
        self::assertSame('app_1', $group->members[0]->id);
        self::assertSame('application', $group->members[0]->kind);
        self::assertSame('app_1.m.ng_1.ng.clever-cloud.com', $group->members[0]->domainName);

        self::assertCount(2, $group->peers);
        $clever = $group->peers[0];
        self::assertSame('peer_1', $clever->id);
        self::assertSame('CleverPeer', $clever->type);
        self::assertSame('hv-par-1', $clever->hv);
        self::assertSame('app_1', $clever->parentMember);
        self::assertNotNull($clever->endpoint);
        self::assertSame('ClientEndpoint', $clever->endpoint->type);
        self::assertSame('10.101.0.2', $clever->endpoint->ngIp);
        self::assertNull($clever->endpoint->ngTerm);

        $external = $group->peers[1];
        self::assertSame('ExternalPeer', $external->type);
        self::assertSame(['192.168.10.0/24'], $external->routedPrefixes);
        self::assertNotNull($external->endpoint);
        self::assertSame('ServerEndpoint', $external->endpoint->type);
        self::assertNotNull($external->endpoint->ngTerm);
        self::assertSame('10.101.0.3', $external->endpoint->ngTerm->host);
        self::assertSame(51820, $external->endpoint->ngTerm->port);
        self::assertNotNull($external->endpoint->publicTerm);
        self::assertSame('203.0.113.7', $external->endpoint->publicTerm->host);
        self::assertSame(51821, $external->endpoint->publicTerm->port);
    }

    public function testListResolvesThePersonalOrganisationAndRepeatsTags(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, []);

        $groups = $this->resource($self, $response)->list(tags: ['prod', 'eu']);

        self::assertSame([], $groups);
        self::assertSame('GET', $self->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame(
            'https://api.clever-cloud.com/v4/networkgroups/organisations/user_1/networkgroups?tags=prod&tags=eu',
            $response->getRequestUrl(),
        );
    }

    public function testSearchTypesEachHitAfterItsDiscriminator(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['type' => 'NetworkGroup'] + self::networkGroupPayload(),
            ['type' => 'Member', 'id' => 'app_1', 'label' => 'web', 'domainName' => 'web.ng', 'kind' => 'application'],
            ['type' => 'ExternalPeer', 'id' => 'peer_2', 'publicKey' => 'pk', 'hostname' => 'laptop', 'parentMember' => 'ext_1', 'endpoint' => ['type' => 'ClientEndpoint', 'ngIp' => '10.101.0.4']],
            ['type' => 'SomethingNew', 'id' => 'x_1'],
        ]);

        $hits = $this->resource($response)->search('web', 'orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/search?query=web', $response->getRequestUrl());
        self::assertCount(3, $hits);
        self::assertInstanceOf(NetworkGroup::class, $hits[0]);
        self::assertSame('ng_1', $hits[0]->id);
        self::assertInstanceOf(NetworkGroupMember::class, $hits[1]);
        self::assertSame('app_1', $hits[1]->id);
        self::assertInstanceOf(NetworkGroupPeer::class, $hits[2]);
        self::assertSame('peer_2', $hits[2]->id);
    }

    public function testGetReturnsTypedGroup(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::networkGroupPayload());

        $group = $this->resource($response)->get('ng_1', 'orga_1');

        self::assertSame('ng_1', $group->id);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1', $response->getRequestUrl());
    }

    public function testCreatePostsLabelOnlyAndAcceptsEmptyBody(): void
    {
        $response = ResourceFactory::emptyResponse(202);

        $this->resource($response)->create('my-ng', organisationId: 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE, $response->getRequestUrl());
        self::assertSame('{"label":"my-ng"}', $response->getRequestOptions()['body']);
    }

    public function testCreatePostsEveryOptionalField(): void
    {
        $response = ResourceFactory::emptyResponse(202);

        $this->resource($response)->create(
            'my-ng',
            'private mesh',
            'orga_1',
            id: 'ng_1',
            tags: ['prod'],
            members: [['id' => 'app_1', 'kind' => 'APPLICATION']],
        );

        self::assertSame(
            '{"label":"my-ng","description":"private mesh","id":"ng_1","tags":["prod"],"members":[{"id":"app_1","kind":"APPLICATION"}]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteHitsDelete(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('ng_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1', $response->getRequestUrl());
    }

    public function testStreamYieldsTypedGroupsAndSkipsHeartbeats(): void
    {
        $frame = json_encode(self::networkGroupPayload(), JSON_THROW_ON_ERROR);
        $response = new MockResponse(
            [
                "event: HEARTBEAT\ndata: {\"id\":\"heartbeat\"}\n\n",
                "data: HEARTBEAT\n\n",
                "data: \n\n",
                'data: '.$frame."\n\n",
            ],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $groups = iterator_to_array($this->resource($response)->stream('ng_1', 'orga_1'), false);

        self::assertCount(1, $groups);
        self::assertSame('ng_1', $groups[0]->id);
        self::assertSame(7, $groups[0]->version);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/stream', $response->getRequestUrl());
    }

    public function testStreamSurfacesNotFoundAsTypedException(): void
    {
        $response = new MockResponse('{"message":"not found"}', [
            'http_code' => 404,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $this->expectException(NotFoundException::class);

        iterator_to_array($this->resource($response)->stream('ng_404', 'orga_1'), false);
    }

    public function testMembersReturnsTypedList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'app_1', 'label' => 'web', 'domainName' => 'web.ng', 'kind' => 'application'],
        ]);

        $members = $this->resource($response)->members('ng_1', 'orga_1');

        self::assertCount(1, $members);
        self::assertSame('web', $members[0]->label);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/members', $response->getRequestUrl());
    }

    public function testMemberReturnsTypedMember(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'app_1', 'label' => 'web', 'domainName' => 'web.ng', 'kind' => 'application',
        ]);

        $member = $this->resource($response)->member('ng_1', 'app_1', 'orga_1');

        self::assertSame('app_1', $member->id);
        self::assertSame('application', $member->kind);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/members/app_1', $response->getRequestUrl());
    }

    public function testAddMemberPostsRequiredFields(): void
    {
        $response = ResourceFactory::emptyResponse(202);

        $this->resource($response)->addMember('ng_1', 'app_1', 'APPLICATION', organisationId: 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/members', $response->getRequestUrl());
        self::assertSame('{"id":"app_1","kind":"APPLICATION"}', $response->getRequestOptions()['body']);
    }

    public function testAddMemberPostsLabelAndDomainName(): void
    {
        $response = ResourceFactory::emptyResponse(202);

        $this->resource($response)->addMember(
            'ng_1',
            'app_1',
            'APPLICATION',
            'web',
            'orga_1',
            'web.m.ng_1.ng.clever-cloud.com',
        );

        self::assertSame(
            '{"id":"app_1","kind":"APPLICATION","label":"web","domainName":"web.m.ng_1.ng.clever-cloud.com"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testRemoveMemberHitsDelete(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->removeMember('ng_1', 'app_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/members/app_1', $response->getRequestUrl());
    }

    public function testPeersReturnsTypedList(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::networkGroupPayload()['peers']);

        $peers = $this->resource($response)->peers('ng_1', 'orga_1');

        self::assertCount(2, $peers);
        self::assertSame('peer_1', $peers[0]->id);
        self::assertSame('peer_2', $peers[1]->id);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/peers', $response->getRequestUrl());
    }

    public function testPeerReturnsTypedPeer(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::networkGroupPayload()['peers'][0]);

        $peer = $this->resource($response)->peer('ng_1', 'peer_1', 'orga_1');

        self::assertSame('peer_1', $peer->id);
        self::assertSame('pk_1', $peer->publicKey);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/peers/peer_1', $response->getRequestUrl());
    }

    public function testAddPeerPostsBodyAndReturnsPeerId(): void
    {
        $response = ResourceFactory::jsonResponse(201, ['peerId' => 'peer_3']);

        $peerId = $this->resource($response)->addPeer(
            'ng_1',
            'peer_3',
            'SERVER',
            'EXTERNAL',
            'ext_1',
            label: 'gateway',
            publicKey: 'pk_3',
            ip: '203.0.113.7',
            port: 51820,
            organisationId: 'orga_1',
        );

        self::assertSame('peer_3', $peerId);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/peers', $response->getRequestUrl());
        self::assertSame(
            '{"id":"peer_3","peerRole":"SERVER","peerKind":"EXTERNAL","parentMember":"ext_1","label":"gateway","publicKey":"pk_3","ip":"203.0.113.7","port":51820}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testRemovePeerHitsDelete(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->removePeer('ng_1', 'peer_2', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/peers/peer_2', $response->getRequestUrl());
    }

    public function testAddExternalPeerPostsBodyAndReturnsPeerId(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['peerId' => 'peer_4']);

        $peerId = $this->resource($response)->addExternalPeer(
            'ng_1',
            'laptop',
            'CLIENT',
            'pk_4',
            'ext_1',
            hostname: 'laptop.local',
            organisationId: 'orga_1',
        );

        self::assertSame('peer_4', $peerId);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/external-peers', $response->getRequestUrl());
        self::assertSame(
            '{"label":"laptop","peerRole":"CLIENT","publicKey":"pk_4","parentMember":"ext_1","hostname":"laptop.local"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testExternalPeerReturnsTypedPeer(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::networkGroupPayload()['peers'][1]);

        $peer = $this->resource($response)->externalPeer('ng_1', 'peer_2', 'orga_1');

        self::assertSame('peer_2', $peer->id);
        self::assertSame('ExternalPeer', $peer->type);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/external-peers/peer_2', $response->getRequestUrl());
    }

    public function testRemoveExternalPeerHitsDelete(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->removeExternalPeer('ng_1', 'peer_2', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/external-peers/peer_2', $response->getRequestUrl());
    }

    public function testWireguardConfigurationReturnsIniText(): void
    {
        $ini = "[Interface]\nPrivateKey = xxx\n";
        $response = new MockResponse($ini, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'text/plain'],
        ]);

        $configuration = $this->resource($response)->wireguardConfiguration('ng_1', 'peer_2', 'orga_1');

        self::assertSame($ini, $configuration);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/ng_1/peers/peer_2/wireguard/configuration', $response->getRequestUrl());
        self::assertSame('Accept: text/plain', ResourceFactory::findHeader($response, 'Accept'));
    }

    public function testWireguardConfigurationPresignedUrlReturnsTheUrl(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['url' => 'https://cellar.example/config?token=t']);

        $url = $this->resource($response)->wireguardConfigurationPresignedUrl('ng_1', 'peer_2', 'orga_1');

        self::assertSame('https://cellar.example/config?token=t', $url);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            self::BASE.'/ng_1/peers/peer_2/wireguard/configuration/presigned-url',
            $response->getRequestUrl(),
        );
    }

    public function testStreamWireguardConfigurationYieldsDecodedEvents(): void
    {
        $response = new MockResponse(
            ["event: HEARTBEAT\ndata: \n\n", "data: {\"configuration\":\"W0ludGVyZmFjZV0K\"}\n\n"],
            ['response_headers' => ['content-type' => 'text/event-stream']],
        );

        $events = iterator_to_array(
            $this->resource($response)->streamWireguardConfiguration('ng_1', 'peer_2', 'orga_1'),
            false,
        );

        self::assertCount(1, $events);
        self::assertSame(['configuration' => 'W0ludGVyZmFjZV0K'], $events[0]->data);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            self::BASE.'/ng_1/peers/peer_2/wireguard/configuration/stream',
            $response->getRequestUrl(),
        );
    }

    /**
     * Wire format of the `NetworkGroup` schema of the Clever Cloud OpenAPI spec.
     *
     * @return array{peers: list<array<string, mixed>>}&array<string, mixed>
     */
    private static function networkGroupPayload(): array
    {
        return [
            'id' => 'ng_1',
            'ownerId' => 'orga_1',
            'label' => 'my-ng',
            'dnsSanitizedLabel' => 'my_ng',
            'description' => 'private mesh',
            'networkIp' => '10.101.0.0/16',
            'lastAllocatedIp' => '10.101.0.3',
            'ownership' => 'customer',
            'tags' => ['prod'],
            'version' => 7,
            'members' => [
                ['id' => 'app_1', 'label' => 'web', 'domainName' => 'app_1.m.ng_1.ng.clever-cloud.com', 'kind' => 'application'],
            ],
            'peers' => [
                [
                    'type' => 'CleverPeer',
                    'id' => 'peer_1',
                    'publicKey' => 'pk_1',
                    'hostname' => 'web-instance',
                    'parentMember' => 'app_1',
                    'hv' => 'hv-par-1',
                    'endpoint' => ['type' => 'ClientEndpoint', 'ngIp' => '10.101.0.2'],
                ],
                [
                    'type' => 'ExternalPeer',
                    'id' => 'peer_2',
                    'label' => 'gateway',
                    'publicKey' => 'pk_2',
                    'hostname' => 'gateway',
                    'parentMember' => 'ext_1',
                    'routedPrefixes' => ['192.168.10.0/24'],
                    'endpoint' => [
                        'type' => 'ServerEndpoint',
                        'ngTerm' => ['host' => '10.101.0.3', 'port' => 51820],
                        'publicTerm' => ['host' => '203.0.113.7', 'port' => 51821],
                    ],
                ],
            ],
        ];
    }

    private function resource(MockResponse ...$responses): NetworkGroupsResource
    {
        return new NetworkGroupsResource(
            ResourceFactory::http(new MockHttpClient($responses)),
            ResourceFactory::mapper(),
        );
    }
}
