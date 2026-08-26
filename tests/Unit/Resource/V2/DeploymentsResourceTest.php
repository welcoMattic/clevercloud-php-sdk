<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\Enum\DeploymentAction;
use CleverCloud\Sdk\Model\Enum\DeploymentState;
use CleverCloud\Sdk\Resource\V2\DeploymentsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(DeploymentsResource::class)]
final class DeploymentsResourceTest extends TestCase
{
    public function testListMapsEnumsAndAppendsLimitQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'dep_1',
                'uuid' => 'uuid-1',
                'action' => 'DEPLOY',
                'state' => 'OK',
                'commit' => 'abc123',
                'date' => 1_700_000_000_000,
            ],
            [
                'id' => 'dep_2',
                'action' => 'DEPLOY',
                'state' => 'FAIL',
            ],
        ]);

        $deps = $this->resource($response)->list('app_1', limit: 5);

        self::assertCount(2, $deps);
        self::assertSame(DeploymentAction::Deploy, $deps[0]->action);
        self::assertSame(DeploymentState::Ok, $deps[0]->state);
        self::assertSame('abc123', $deps[0]->commit);
        self::assertSame(DeploymentState::Fail, $deps[1]->state);
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/deployments?limit=5',
            $response->getRequestUrl(),
        );
    }

    public function testListMapsAuthorAndInstances(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => '37',
                'uuid' => 'deployment_791e2337',
                'action' => 'UNDEPLOY',
                'state' => 'OK',
                'commit' => null,
                'date' => 1780606152629,
                'instances' => 0,
                'author' => [
                    'id' => 'user_b05c182b-cfec-4509-81f0-6b6bd3f85c49',
                    'name' => 'Mathieu Santostefano',
                ],
                'cause' => 'Console - Clever Cloud',
            ],
        ]);

        $deps = $this->resource($response)->list('app_1');

        self::assertCount(1, $deps);
        self::assertSame(0, $deps[0]->instances);
        self::assertNotNull($deps[0]->author);
        self::assertSame('user_b05c182b-cfec-4509-81f0-6b6bd3f85c49', $deps[0]->author->id);
        self::assertSame('Mathieu Santostefano', $deps[0]->author->name);
        self::assertSame('Console - Clever Cloud', $deps[0]->cause);
    }

    public function testCancelHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->cancel('app_1', 'dep_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/deployments/dep_1',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): DeploymentsResource
    {
        return new DeploymentsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
