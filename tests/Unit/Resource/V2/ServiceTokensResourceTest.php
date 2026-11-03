<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Model\Enum\ServiceTokenRole;
use CleverCloud\Sdk\Model\Enum\ServiceTokenStatus;
use CleverCloud\Sdk\Resource\V2\ServiceTokensResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ServiceTokensResource::class)]
final class ServiceTokensResourceTest extends TestCase
{
    public function testClientExposesResource(): void
    {
        $client = new ClientBuilder()
            ->withCredentials(Credentials::oauth1('ck', 'cs', 'tk', 'ts'))
            ->build();

        self::assertSame($client->serviceTokens, $client->serviceTokens);
    }

    public function testListReturnsTypedTokens(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            self::metadata('st_1') + ['creatorUserId' => 'user_1'],
            self::metadata('st_2', 'REVOKED') + ['creatorUserId' => null],
        ]);

        $tokens = $this->resource($response)->list('orga_1');

        self::assertCount(2, $tokens);
        self::assertSame('st_1', $tokens[0]->id);
        self::assertSame('tenant_1', $tokens[0]->tenantId);
        self::assertSame('rev_st_1', $tokens[0]->revocationId);
        self::assertSame(ServiceTokenStatus::Active, $tokens[0]->status);
        self::assertSame('CI', $tokens[0]->name);
        self::assertSame('SELF_SUSTAINED_ACCESS', $tokens[0]->tokenType);
        self::assertSame('2026-01-01T00:00:00Z', $tokens[0]->createdAt);
        self::assertSame('2026-04-01T00:00:00Z', $tokens[0]->expiredAt);
        self::assertNull($tokens[0]->revokedAt);
        self::assertSame('user_1', $tokens[0]->creatorUserId);
        self::assertSame(ServiceTokenStatus::Revoked, $tokens[1]->status);
        self::assertNull($tokens[1]->creatorUserId);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens', $response->getRequestUrl());
    }

    public function testListForwardsLimitAndOffset(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->list('orga_1', limit: 10, offset: 20);

        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens?limit=10&offset=20',
            $response->getRequestUrl(),
        );
    }

    public function testListPageSendsPaginateAndCursor(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'items' => [self::metadata('st_1') + ['creatorUserId' => 'user_1']],
            'nextCursor' => 'cur_2',
        ]);

        $page = $this->resource($response)->listPage('orga_1', limit: 50, cursor: 'cur_1');

        self::assertCount(1, $page->items);
        self::assertSame('st_1', $page->items[0]->id);
        self::assertSame('user_1', $page->items[0]->creatorUserId);
        self::assertSame('cur_2', $page->nextCursor);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens?paginate=1&limit=50&cursor=cur_1',
            $response->getRequestUrl(),
        );
    }

    public function testListPageFirstPageOmitsCursor(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['items' => [], 'nextCursor' => null]);

        $page = $this->resource($response)->listPage('orga_1');

        self::assertSame([], $page->items);
        self::assertNull($page->nextCursor);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens?paginate=1',
            $response->getRequestUrl(),
        );
    }

    public function testCreatePostsFullPayloadAndReturnsTokenValue(): void
    {
        $response = ResourceFactory::jsonResponse(201, [
            'metadata' => self::metadata('st_new'),
            'token' => 'En0KEwoEYmlzY3VpdA',
            'refreshToken' => null,
        ]);

        $created = $this->resource($response)->create(
            'orga_1',
            name: 'CI',
            role: ServiceTokenRole::Developer,
            description: 'Deploys from CI',
            resources: ['app_1', 'addon_1'],
            ttlSeconds: 86400,
        );

        self::assertSame('En0KEwoEYmlzY3VpdA', $created->token);
        self::assertNull($created->refreshToken);
        self::assertSame('st_new', $created->metadata->id);
        self::assertSame(ServiceTokenStatus::Active, $created->metadata->status);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens', $response->getRequestUrl());
        self::assertSame(
            '{"name":"CI","role":"DEVELOPER","description":"Deploys from CI","resources":["app_1","addon_1"],"ttl_seconds":86400}',
            $response->getRequestOptions()['body'],
        );
        self::assertStringStartsWith('Authorization: OAuth ', (string) ResourceFactory::findHeader($response, 'Authorization'));
    }

    public function testCreateOmitsOptionalFields(): void
    {
        $response = ResourceFactory::jsonResponse(201, [
            'metadata' => self::metadata('st_new'),
            'token' => 'En0KEwoEYmlzY3VpdA',
            'refreshToken' => null,
        ]);

        $this->resource($response)->create('orga_1', 'CI', ServiceTokenRole::Admin);

        self::assertSame('{"name":"CI","role":"ADMIN"}', $response->getRequestOptions()['body']);
    }

    public function testGetReturnsSingleToken(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::metadata('st_1') + ['creatorUserId' => 'user_1']);

        $token = $this->resource($response)->get('orga_1', 'st_1');

        self::assertSame('st_1', $token->id);
        self::assertSame('user_1', $token->creatorUserId);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens/st_1',
            $response->getRequestUrl(),
        );
    }

    public function testDeleteRevokesToken(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('orga_1', 'rev_st_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/service-tokens/rev_st_1',
            $response->getRequestUrl(),
        );
    }

    /**
     * Every key `ServiceTokenMetadata` declares as required.
     *
     * @return array<string, mixed>
     */
    private static function metadata(string $id, string $status = 'ACTIVE'): array
    {
        return [
            'id' => $id,
            'tenantId' => 'tenant_1',
            'productId' => 'product_1',
            'locationId' => 'location_1',
            'tokenType' => 'SELF_SUSTAINED_ACCESS',
            'name' => 'CI',
            'description' => null,
            'revocationId' => 'rev_'.$id,
            'resourceId' => null,
            'tags' => [],
            'instigatorId' => 'instigator_1',
            'status' => $status,
            'createdAt' => '2026-01-01T00:00:00Z',
            'expiredAt' => '2026-04-01T00:00:00Z',
            'revokedAt' => 'REVOKED' === $status ? '2026-02-01T00:00:00Z' : null,
        ];
    }

    private function resource(MockResponse $response): ServiceTokensResource
    {
        return new ServiceTokensResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
