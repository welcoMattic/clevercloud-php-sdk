<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\AddonsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(AddonsResource::class)]
final class AddonsResourceTest extends TestCase
{
    public function testListMapsNestedProviderAndPlan(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'addon_1',
                'name' => 'my-pg',
                'realId' => 'postgresql_1',
                'region' => 'par',
                'provider' => ['id' => 'postgresql-addon', 'name' => 'PostgreSQL'],
                'plan' => ['id' => 'plan_dev', 'slug' => 'dev', 'name' => 'Dev'],
                'configKeys' => ['POSTGRESQL_ADDON_URI'],
                'creationDate' => 1_700_000_000_000,
            ],
        ]);

        $addons = $this->resource($response)->list('orga_1');

        self::assertCount(1, $addons);
        self::assertSame('addon_1', $addons[0]->id);
        self::assertSame('postgresql_1', $addons[0]->realId);
        self::assertNotNull($addons[0]->provider);
        self::assertSame('postgresql-addon', $addons[0]->provider->id);
        self::assertSame('PostgreSQL', $addons[0]->provider->name);
        self::assertNotNull($addons[0]->plan);
        self::assertSame('dev', $addons[0]->plan->slug);
        self::assertSame(['POSTGRESQL_ADDON_URI'], $addons[0]->configKeys);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/addons', $response->getRequestUrl());
    }

    public function testEnvFlattensList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'POSTGRESQL_ADDON_URI', 'value' => 'postgres://x:y@host/db'],
            ['name' => 'POSTGRESQL_ADDON_HOST', 'value' => 'host'],
        ]);

        $env = $this->resource($response)->env('addon_1');

        self::assertSame([
            'POSTGRESQL_ADDON_URI' => 'postgres://x:y@host/db',
            'POSTGRESQL_ADDON_HOST' => 'host',
        ], $env);
    }

    public function testProvidersHitsProductsEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'redis-addon', 'name' => 'Redis'],
            ['id' => 'postgresql-addon', 'name' => 'PostgreSQL'],
        ]);

        $providers = $this->resource($response)->providers();

        self::assertCount(2, $providers);
        self::assertSame('redis-addon', $providers[0]->id);
        self::assertSame('https://api.clever-cloud.com/v2/products/addonproviders', $response->getRequestUrl());
    }

    public function testCreatePostsBody(): void
    {
        $response = ResourceFactory::jsonResponse(201, ['id' => 'addon_new', 'name' => 'pg', 'realId' => 'postgresql_new']);

        $addon = $this->resource($response)->create([
            'name' => 'pg',
            'region' => 'par',
            'providerId' => 'postgresql-addon',
            'plan' => 'plan_dev',
        ]);

        self::assertSame('addon_new', $addon->id);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/addons', $response->getRequestUrl());
        self::assertSame(
            '{"name":"pg","region":"par","providerId":"postgresql-addon","plan":"plan_dev"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->delete('addon_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1', $response->getRequestUrl());
    }

    public function testSsoReturnsRawPayload(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'url' => 'https://addon.example/sso',
            'signature' => 'abc123',
            'timestamp' => 1_700_000_000,
        ]);

        $sso = $this->resource($response)->sso('addon_1');

        self::assertSame('https://addon.example/sso', $sso['url']);
        self::assertSame('https://api.clever-cloud.com/v2/self/addons/addon_1/sso', $response->getRequestUrl());
    }

    public function testMigratePostsPlan(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'addon_1', 'name' => 'pg']);

        $this->resource($response)->migrate('addon_1', 'plan_prod', 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1/migrations',
            $response->getRequestUrl(),
        );
        self::assertSame('{"plan":"plan_prod"}', $response->getRequestOptions()['body']);
    }

    public function testListMigrationsReturnsRawList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'mig_1', 'status' => 'success'],
            ['id' => 'mig_2', 'status' => 'in-progress'],
        ]);

        $migrations = $this->resource($response)->listMigrations('addon_1', 'orga_1');

        self::assertCount(2, $migrations);
        self::assertSame('mig_1', $migrations[0]['id']);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1/migrations',
            $response->getRequestUrl(),
        );
    }

    public function testGetMigrationFetchesSingleId(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'mig_1', 'status' => 'success']);

        $migration = $this->resource($response)->getMigration('addon_1', 'mig_1', 'orga_1');

        self::assertSame('mig_1', $migration['id']);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1/migrations/mig_1',
            $response->getRequestUrl(),
        );
    }

    public function testCancelMigrationHitsDelete(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->cancelMigration('addon_1', 'mig_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1/migrations/mig_1',
            $response->getRequestUrl(),
        );
    }

    public function testPreorderMigrationPostsPlan(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['totalPrice' => 12.5]);

        $preorder = $this->resource($response)->preorderMigration('addon_1', 'plan_prod', 'orga_1');

        self::assertSame(12.5, $preorder['totalPrice']);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1/migrations/preorder',
            $response->getRequestUrl(),
        );
        self::assertSame('{"plan":"plan_prod"}', $response->getRequestOptions()['body']);
    }

    private function resource(MockResponse $response): AddonsResource
    {
        return new AddonsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
