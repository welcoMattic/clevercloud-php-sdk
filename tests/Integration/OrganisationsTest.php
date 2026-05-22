<?php

namespace CleverCloud\Sdk\Tests\Integration;

use CleverCloud\Sdk\Exception\NotFoundException;

final class OrganisationsTest extends IntegrationTestCase
{
    public function testListReturnsTypedOrganisations(): void
    {
        $organisations = $this->client->organisations->list();

        self::assertIsList($organisations);
        foreach ($organisations as $organisation) {
            self::assertStringStartsWith('orga_', $organisation->id);
            self::assertNotEmpty($organisation->name);
        }
    }

    public function testGetByIdReturnsSameShape(): void
    {
        $organisationId = $this->targetOrganisationId();

        $organisation = $this->client->organisations->get($organisationId);

        self::assertSame($organisationId, $organisation->id);
        self::assertNotEmpty($organisation->name);
    }

    public function testListMembersReturnsTypedCollection(): void
    {
        $organisationId = $this->targetOrganisationId();

        $members = $this->client->organisations->members($organisationId);

        self::assertIsList($members);
        // Field-level assertions skipped — Member shape is fully nullable.
        // The fact that map() didn't throw is the integration guarantee here.
    }

    public function testGetUnknownOrganisationThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->client->organisations->get('orga_00000000-0000-0000-0000-000000000000');
    }
}
