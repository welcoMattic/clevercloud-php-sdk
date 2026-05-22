<?php

namespace CleverCloud\Sdk\Tests\Integration;

use CleverCloud\Sdk\Model\Deployment;

final class DeploymentsTest extends IntegrationTestCase
{
    public function testListIsTypedAndHonoursLimit(): void
    {
        $organisationId = $this->targetOrganisationId();
        $applicationId = $this->targetApplicationId($organisationId);

        $deployments = $this->client->deployments->list($applicationId, $organisationId, limit: 5);

        self::assertIsList($deployments);
        self::assertLessThanOrEqual(5, \count($deployments), 'limit=5 must cap the page size.');

        foreach ($deployments as $deployment) {
            self::assertInstanceOf(Deployment::class, $deployment);
            self::assertNotEmpty($deployment->id);
        }
    }
}
