<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\ProductsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProductsResource::class)]
final class ProductsResourceTest extends TestCase
{
    public function testInstancesHydratesInstanceTypesWithFlavors(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            [
                'type' => 'node',
                'version' => '20',
                'enabled' => true,
                'flavors' => [
                    ['name' => 'S', 'mem' => 1024, 'cpus' => 1, 'price' => 0.34],
                    ['name' => 'M', 'mem' => 2048, 'cpus' => 2, 'price' => 0.68],
                ],
            ],
        ]));

        $types = $this->resource($psr18)->instances();

        self::assertCount(1, $types);
        self::assertSame('node', $types[0]->type);
        self::assertSame('20', $types[0]->version);
        self::assertCount(2, $types[0]->flavors);
        self::assertSame('S', $types[0]->flavors[0]->name);
        self::assertSame(1024, $types[0]->flavors[0]->mem);
        self::assertSame(0.34, $types[0]->flavors[0]->price);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v4/products/instances', (string) $psr18->lastRequest->getUri());
    }

    public function testZonesHydratesZones(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['name' => 'par', 'city' => 'Paris', 'country' => 'France', 'countryCode' => 'FR'],
            ['name' => 'mtl', 'city' => 'Montreal', 'country' => 'Canada', 'countryCode' => 'CA'],
        ]));

        $zones = $this->resource($psr18)->zones();

        self::assertCount(2, $zones);
        self::assertSame('par', $zones[0]->name);
        self::assertSame('Paris', $zones[0]->city);
        self::assertSame('FR', $zones[0]->countryCode);
    }

    public function testCountriesHydratesCountries(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['code' => 'FR', 'name' => 'France', 'eu' => true],
            ['code' => 'US', 'name' => 'United States', 'eu' => false],
        ]));

        $countries = $this->resource($psr18)->countries();

        self::assertCount(2, $countries);
        self::assertSame('FR', $countries[0]->code);
        self::assertTrue($countries[0]->eu);
        self::assertFalse($countries[1]->eu);
    }

    private function resource(RecordingClient $psr18): ProductsResource
    {
        return new ProductsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
