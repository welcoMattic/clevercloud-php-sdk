<?php

namespace CleverCloud\Sdk\Tests\Integration;

/**
 * Hits /v4/zones. Doubles as a smoke test for V4 routing — separate from V2
 * which the rest of the suite covers.
 */
final class ZonesTest extends IntegrationTestCase
{
    public function testListReturnsTypedZones(): void
    {
        $zones = $this->client->zones->list();

        self::assertIsList($zones);
        self::assertNotEmpty($zones);

        $names = [];
        foreach ($zones as $zone) {
            self::assertNotEmpty($zone->name);
            self::assertIsArray($zone->tags);
            $names[] = $zone->name;
        }

        self::assertContains('par', $names);
    }

    public function testGetParisZoneIsConsistent(): void
    {
        $zone = $this->client->zones->get('par');

        self::assertSame('par', $zone->name);
    }
}
