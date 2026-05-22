<?php

namespace CleverCloud\Sdk\Tests\Integration;

/**
 * Catalog endpoints under /v2/products/*. These return public reference data
 * (instance types, addon providers, zones, countries) and should always
 * succeed regardless of which account is authenticated.
 *
 * These tests pin the SDK's V2 routing for products — previously the same
 * resource lived under V4 and 404'd in production. Treat the V2 routing
 * here as a regression gate.
 */
final class ProductsTest extends IntegrationTestCase
{
    public function testInstancesReturnsTypedInstanceTypes(): void
    {
        $types = $this->client->products->instances();

        self::assertIsList($types);
        self::assertNotEmpty($types, 'Clever Cloud catalog always has at least one runtime.');

        foreach ($types as $type) {
            self::assertNotEmpty($type->type);
            self::assertIsList($type->flavors);
            foreach ($type->flavors as $flavor) {
                self::assertNotEmpty($flavor->name);
                // memory was previously typed as ?int and broke mapping for
                // every runtime; this guards against the regression.
                self::assertIsArray($flavor->memory);
            }
        }
    }

    public function testAddonProvidersMirrorsAddonsCatalog(): void
    {
        $providers = $this->client->products->addonProviders();

        self::assertIsList($providers);
        self::assertNotEmpty($providers);
        foreach ($providers as $provider) {
            self::assertNotEmpty($provider->id);
        }
    }

    public function testZonesIncludeKnownDefaults(): void
    {
        $zones = $this->client->products->zones();

        self::assertIsList($zones);
        self::assertNotEmpty($zones);

        $names = [];
        foreach ($zones as $zone) {
            self::assertNotEmpty($zone->name);
            // tags previously typed as ?string but the API returns a list,
            // breaking every zone-mapping call; lock the shape.
            self::assertIsArray($zone->tags);
            $names[] = $zone->name;
        }

        self::assertContains('par', $names, 'The Paris (par) zone is part of the public catalog.');
    }

    public function testCountriesReturnsTypedCatalog(): void
    {
        $countries = $this->client->products->countries();

        self::assertIsList($countries);
        self::assertNotEmpty($countries);
        // The fact that the mapping succeeded validates the schema —
        // Country has only nullable fields beyond the discriminator.
    }
}
