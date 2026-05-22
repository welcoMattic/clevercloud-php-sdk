<?php

namespace CleverCloud\Sdk\Tests\Integration;

final class AddonsTest extends IntegrationTestCase
{
    public function testListProvidersReturnsCatalog(): void
    {
        $providers = $this->client->addons->providers();

        self::assertIsList($providers);
        self::assertNotEmpty($providers, 'Clever Cloud always exposes at least one addon provider.');

        $byId = [];
        foreach ($providers as $provider) {
            self::assertNotEmpty($provider->id);
            $byId[$provider->id] = true;
        }

        self::assertCount(\count($providers), $byId, 'Provider ids must be unique.');
        self::assertArrayHasKey('postgresql-addon', $byId);
        self::assertArrayHasKey('redis-addon', $byId);
    }

    public function testProviderPlansReturnsRawCatalogShape(): void
    {
        // plans() returns the raw API payload, not hydrated AddonPlan DTOs —
        // verified against src/Resource/V2/AddonsResource.php.
        $plans = $this->client->addons->plans('postgresql-addon');

        self::assertIsList($plans);
        self::assertNotEmpty($plans, 'postgresql-addon always exposes plans.');
        foreach ($plans as $plan) {
            self::assertIsArray($plan);
            self::assertArrayHasKey('id', $plan);
            self::assertArrayHasKey('slug', $plan);
        }
    }

    public function testListOrgAddonsReturnsTypedCollection(): void
    {
        $organisationId = $this->targetOrganisationId();

        $addons = $this->client->addons->list($organisationId);

        self::assertIsList($addons);
        foreach ($addons as $addon) {
            self::assertNotEmpty($addon->id);
            self::assertNotEmpty($addon->name);
        }
    }
}
