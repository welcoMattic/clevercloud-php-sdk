<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\Operator\KeycloakResource;
use CleverCloud\Sdk\Resource\V4\Operator\MatomoResource;
use CleverCloud\Sdk\Resource\V4\Operator\MetabaseResource;
use CleverCloud\Sdk\Resource\V4\Operator\OtoroshiResource;
use CleverCloud\Sdk\Resource\V4\OperatorsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(KeycloakResource::class)]
#[CoversClass(MatomoResource::class)]
#[CoversClass(MetabaseResource::class)]
#[CoversClass(OtoroshiResource::class)]
#[CoversClass(OperatorsResource::class)]
final class OperatorsResourceTest extends TestCase
{
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

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function networkGroupLinks(): iterable
    {
        yield 'keycloak link' => ['keycloak', 'POST', 'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/op_1/networkgroup'];
        yield 'keycloak unlink' => ['keycloak', 'DELETE', 'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/op_1/networkgroup'];
        yield 'otoroshi link' => ['otoroshi', 'POST', 'https://api.clever-cloud.com/v4/addon-providers/addon-otoroshi/addons/op_1/networkgroup'];
        yield 'otoroshi unlink' => ['otoroshi', 'DELETE', 'https://api.clever-cloud.com/v4/addon-providers/addon-otoroshi/addons/op_1/networkgroup'];
    }

    #[DataProvider('networkGroupLinks')]
    public function testNetworkGroupLinkHitsNetworkgroupSubpath(string $operator, string $method, string $url): void
    {
        $response = ResourceFactory::jsonResponse(200, []);
        $facade = $this->facade($response);

        match ([$operator, $method]) {
            ['keycloak', 'POST'] => $facade->keycloak->linkNetworkGroup('op_1'),
            ['keycloak', 'DELETE'] => $facade->keycloak->unlinkNetworkGroup('op_1'),
            ['otoroshi', 'POST'] => $facade->otoroshi->linkNetworkGroup('op_1'),
            default => $facade->otoroshi->unlinkNetworkGroup('op_1'),
        };

        self::assertSame($method, $response->getRequestMethod());
        self::assertSame($url, $response->getRequestUrl());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providerInformationPaths(): iterable
    {
        yield 'keycloak' => ['keycloak', 'https://api.clever-cloud.com/v4/addon-providers/keycloak'];
        yield 'matomo' => ['matomo', 'https://api.clever-cloud.com/v4/addon-providers/addon-matomo'];
        yield 'metabase' => ['metabase', 'https://api.clever-cloud.com/v4/addon-providers/metabase'];
        yield 'otoroshi' => ['otoroshi', 'https://api.clever-cloud.com/v4/addon-providers/otoroshi'];
    }

    #[DataProvider('providerInformationPaths')]
    public function testProviderInformationFollowsEachOperatorPath(string $operator, string $url): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'providerId' => 'keycloak',
            'defaultDedicatedVersion' => '26.1',
            'clusters' => [
                ['id' => 'cluster_1', 'label' => 'Paris', 'zone' => 'par', 'version' => '1', 'features' => ['ng']],
            ],
            'dedicated' => ['26.1' => ['features' => []]],
        ]);

        $facade = $this->facade($response);
        $info = match ($operator) {
            'keycloak' => $facade->keycloak->providerInformation(),
            'matomo' => $facade->matomo->providerInformation(),
            'metabase' => $facade->metabase->providerInformation(),
            default => $facade->otoroshi->providerInformation(),
        };

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame($url, $response->getRequestUrl());
        self::assertSame('keycloak', $info->providerId);
        self::assertSame('26.1', $info->defaultDedicatedVersion);
        self::assertCount(1, $info->clusters);
        self::assertSame('cluster_1', $info->clusters[0]->id);
        self::assertSame('par', $info->clusters[0]->zone);
        self::assertSame(['ng'], $info->clusters[0]->features);
        self::assertSame(['26.1' => ['features' => []]], $info->dedicated);
    }

    public function testKeycloakCheckVersionReturnsTypedResult(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'available' => ['25.0', '26.1'],
            'installed' => '25.0',
            'latest' => '26.1',
            'needUpdate' => true,
        ]);

        $check = $this->facade($response)->keycloak->checkVersion('keycloak_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/keycloak_1/version/check',
            $response->getRequestUrl(),
        );
        self::assertSame(['25.0', '26.1'], $check->available);
        self::assertSame('25.0', $check->installed);
        self::assertSame('26.1', $check->latest);
        self::assertTrue($check->needUpdate);
        self::assertNull($check->requiresApplicationUpdate);
    }

    public function testMetabaseCheckVersionMapsRequiresApplicationUpdate(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'available' => ['v0.52'],
            'installed' => 'v0.52',
            'latest' => 'v0.52',
            'needUpdate' => false,
            'requiresApplicationUpdate' => false,
        ]);

        $check = $this->facade($response)->metabase->checkVersion('metabase_1');

        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-metabase/addons/metabase_1/version/check',
            $response->getRequestUrl(),
        );
        self::assertFalse($check->needUpdate);
        self::assertFalse($check->requiresApplicationUpdate);
    }

    public function testOtoroshiCheckVersionHitsVersionCheck(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'available' => ['16.20'],
            'installed' => '16.20',
            'latest' => '16.20',
            'needUpdate' => false,
        ]);

        $this->facade($response)->otoroshi->checkVersion('otoroshi_1');

        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-otoroshi/addons/otoroshi_1/version/check',
            $response->getRequestUrl(),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function versionUpdatePaths(): iterable
    {
        yield 'keycloak' => ['keycloak', 'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/op_1/version/update'];
        yield 'metabase' => ['metabase', 'https://api.clever-cloud.com/v4/addon-providers/addon-metabase/addons/op_1/version/update'];
        yield 'otoroshi' => ['otoroshi', 'https://api.clever-cloud.com/v4/addon-providers/addon-otoroshi/addons/op_1/version/update'];
    }

    #[DataProvider('versionUpdatePaths')]
    public function testUpdateVersionPostsTargetVersion(string $operator, string $url): void
    {
        $response = ResourceFactory::jsonResponse(200, ['addonId' => 'addon_1', 'version' => '26.1']);

        $facade = $this->facade($response);
        $addon = match ($operator) {
            'keycloak' => $facade->keycloak->updateVersion('op_1', '26.1'),
            'metabase' => $facade->metabase->updateVersion('op_1', '26.1'),
            default => $facade->otoroshi->updateVersion('op_1', '26.1'),
        };

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame($url, $response->getRequestUrl());
        self::assertSame('{"targetVersion":"26.1"}', $response->getRequestOptions()['body']);
        self::assertSame(['addonId' => 'addon_1', 'version' => '26.1'], $addon);
    }

    public function testOtoroshiConfigYamlReturnsRawYaml(): void
    {
        $yaml = "otoroshi:\n  url: https://otoroshi.example\n";
        $response = new MockResponse($yaml, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/yaml'],
        ]);

        $config = $this->facade($response)->otoroshi->configYaml('otoroshi_1');

        self::assertSame($yaml, $config);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-otoroshi/addons/otoroshi_1/config.yaml',
            $response->getRequestUrl(),
        );
        self::assertSame('Accept: application/yaml', ResourceFactory::findHeader($response, 'Accept'));
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
