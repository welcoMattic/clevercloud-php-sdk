<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\Operator\KeycloakResource;
use CleverCloud\Sdk\Resource\V4\Operator\MatomoResource;
use CleverCloud\Sdk\Resource\V4\OperatorsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(KeycloakResource::class)]
#[CoversClass(MatomoResource::class)]
#[CoversClass(OperatorsResource::class)]
final class OperatorsResourceTest extends TestCase
{
    public function testKeycloakListRoutesUnderAddonProviders(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'op_1', 'name' => 'idp-prod', 'region' => 'par'],
        ]);

        $ops = $this->facade($response)->keycloak->list();

        self::assertCount(1, $ops);
        self::assertSame('op_1', $ops[0]->id);
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons',
            $response->getRequestUrl(),
        );
    }

    public function testMatomoCreatePostsBody(): void
    {
        $response = ResourceFactory::jsonResponse(201, ['id' => 'op_new', 'name' => 'analytics']);

        $this->facade($response)->matomo->create(['name' => 'analytics', 'region' => 'par']);

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-matomo/addons',
            $response->getRequestUrl(),
        );
        self::assertSame('{"name":"analytics","region":"par"}', $response->getRequestOptions()['body']);
    }

    public function testRebootPostsToRebootSubpath(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->facade($response)->keycloak->reboot('op_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/op_1/reboot',
            $response->getRequestUrl(),
        );
    }

    public function testRebuildPostsToRebuildSubpath(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->facade($response)->matomo->rebuild('op_1');

        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-matomo/addons/op_1/rebuild',
            $response->getRequestUrl(),
        );
    }

    public function testLinkNetworkGroupHitsNetworkgroupSubpath(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->facade($response)->keycloak->linkNetworkGroup('op_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/op_1/networkgroup',
            $response->getRequestUrl(),
        );
    }

    public function testSubResourcesAreMemoised(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);
        $facade = $this->facade($response);

        self::assertSame($facade->keycloak, $facade->keycloak);
        self::assertSame($facade->matomo, $facade->matomo);
    }

    private function facade(MockResponse $response): OperatorsResource
    {
        return new OperatorsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
