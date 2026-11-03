<?php

namespace CleverCloud\Sdk\Tests\Unit\Model;

use CleverCloud\Sdk\Model\Operator;
use CleverCloud\Sdk\Resource\V4\Operator\KeycloakResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Pins the live shape of `GET /v4/addon-providers/addon-keycloak/addons/{id}`:
 * no `id` field, the operator id comes as `resourceId` and the V2 add-on id as
 * `addonId`.
 */
#[CoversClass(Operator::class)]
final class OperatorMappingTest extends TestCase
{
    public function testGetMapsTheLiveKeycloakShape(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'resourceId' => 'keycloak_1',
            'addonId' => 'addon_1',
            'name' => 'idp',
            'ownerId' => 'orga_1',
            'plan' => 'base',
            'version' => '26.1.0',
            'javaVersion' => '21',
            'accessUrl' => 'https://idp.example.com',
            'availableVersions' => ['26.1.0'],
            'features' => ['networkGroup' => null],
            'resources' => ['entrypoint' => 'app_1'],
            'envVars' => [],
        ]);
        $resource = new KeycloakResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );

        $operator = $resource->get('keycloak_1');

        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-keycloak/addons/keycloak_1',
            $response->getRequestUrl(),
        );
        self::assertSame('keycloak_1', $operator->id);
        self::assertSame('keycloak_1', $operator->resourceId);
        self::assertSame('addon_1', $operator->addonId);
        self::assertSame('idp', $operator->name);
        self::assertSame('orga_1', $operator->ownerId);
        self::assertSame('26.1.0', $operator->version);
        self::assertSame('https://idp.example.com', $operator->accessUrl);
    }
}
