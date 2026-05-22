<?php

namespace CleverCloud\Sdk\Tests\Integration;

use CleverCloud\Sdk\Exception\NotFoundException;

final class ApplicationsTest extends IntegrationTestCase
{
    public function testListReturnsTypedApplications(): void
    {
        $organisationId = $this->targetOrganisationId();

        $applications = $this->client->applications->list($organisationId);

        self::assertIsList($applications);
        foreach ($applications as $application) {
            self::assertStringStartsWith('app_', $application->id);
            self::assertNotEmpty($application->name);
        }
    }

    public function testGetByIdReturnsHydratedApplication(): void
    {
        $organisationId = $this->targetOrganisationId();
        $applicationId = $this->targetApplicationId($organisationId);

        $application = $this->client->applications->get($applicationId, $organisationId);

        self::assertSame($applicationId, $application->id);
        self::assertNotEmpty($application->name);
        // creationDate is a unix epoch in seconds; sanity-check it's plausible.
        if (null !== $application->creationDate) {
            self::assertGreaterThan(1_400_000_000, $application->creationDate);
        }
    }

    public function testListVhostsForApplication(): void
    {
        $organisationId = $this->targetOrganisationId();
        $applicationId = $this->targetApplicationId($organisationId);

        $vhosts = $this->client->domains->list($applicationId, $organisationId);

        self::assertIsList($vhosts);
        foreach ($vhosts as $vhost) {
            self::assertNotEmpty($vhost->fqdn);
        }
    }

    public function testGetUnknownApplicationThrowsNotFound(): void
    {
        $organisationId = $this->targetOrganisationId();

        $this->expectException(NotFoundException::class);

        $this->client->applications->get('app_00000000-0000-0000-0000-000000000000', $organisationId);
    }
}
