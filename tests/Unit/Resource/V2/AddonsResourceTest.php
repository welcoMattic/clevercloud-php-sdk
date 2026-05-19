<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\AddonsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddonsResource::class)]
final class AddonsResourceTest extends TestCase
{
    public function testListMapsNestedProviderAndPlan(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
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
        ]));

        $addons = $this->resource($psr18)->list('orga_1');

        self::assertCount(1, $addons);
        self::assertSame('addon_1', $addons[0]->id);
        self::assertSame('postgresql_1', $addons[0]->realId);
        self::assertNotNull($addons[0]->provider);
        self::assertSame('postgresql-addon', $addons[0]->provider->id);
        self::assertSame('PostgreSQL', $addons[0]->provider->name);
        self::assertNotNull($addons[0]->plan);
        self::assertSame('dev', $addons[0]->plan->slug);
        self::assertSame(['POSTGRESQL_ADDON_URI'], $addons[0]->configKeys);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/addons', (string) $psr18->lastRequest->getUri());
    }

    public function testEnvFlattensList(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['name' => 'POSTGRESQL_ADDON_URI', 'value' => 'postgres://x:y@host/db'],
            ['name' => 'POSTGRESQL_ADDON_HOST', 'value' => 'host'],
        ]));

        $env = $this->resource($psr18)->env('addon_1');

        self::assertSame([
            'POSTGRESQL_ADDON_URI' => 'postgres://x:y@host/db',
            'POSTGRESQL_ADDON_HOST' => 'host',
        ], $env);
    }

    public function testProvidersHitsProductsEndpoint(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['id' => 'redis-addon', 'name' => 'Redis'],
            ['id' => 'postgresql-addon', 'name' => 'PostgreSQL'],
        ]));

        $providers = $this->resource($psr18)->providers();

        self::assertCount(2, $providers);
        self::assertSame('redis-addon', $providers[0]->id);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/products/addonproviders', (string) $psr18->lastRequest->getUri());
    }

    public function testCreatePostsBody(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(201, ['id' => 'addon_new', 'name' => 'pg', 'realId' => 'postgresql_new']));

        $addon = $this->resource($psr18)->create([
            'name' => 'pg',
            'region' => 'par',
            'providerId' => 'postgresql-addon',
            'plan' => 'plan_dev',
        ]);

        self::assertSame('addon_new', $addon->id);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/addons', (string) $psr18->lastRequest->getUri());
        self::assertSame(
            '{"name":"pg","region":"par","providerId":"postgresql-addon","plan":"plan_dev"}',
            (string) $psr18->lastRequest->getBody(),
        );
    }

    public function testDeleteHitsDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->delete('addon_1', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/addons/addon_1', (string) $psr18->lastRequest->getUri());
    }

    private function resource(RecordingClient $psr18): AddonsResource
    {
        return new AddonsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
