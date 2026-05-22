<?php

namespace CleverCloud\Sdk\Tests\Integration;

/**
 * Verifies the {name, value} → name=>value collapse the SDK performs on env
 * var reads. Read-only — no writes against the user's account.
 */
final class EnvironmentTest extends IntegrationTestCase
{
    public function testListReturnsNameValueMap(): void
    {
        $organisationId = $this->targetOrganisationId();
        $applicationId = $this->targetApplicationId($organisationId);

        $env = $this->client->environment->list($applicationId, $organisationId);

        self::assertIsArray($env);
        // Map shape: keys are env var names (strings), values are strings.
        foreach ($env as $name => $value) {
            self::assertIsString($name);
            self::assertNotSame('', $name);
            self::assertIsString($value);
        }
    }
}
