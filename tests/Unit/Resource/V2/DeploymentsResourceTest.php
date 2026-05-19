<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\Enum\DeploymentAction;
use CleverCloud\Sdk\Model\Enum\DeploymentState;
use CleverCloud\Sdk\Resource\V2\DeploymentsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeploymentsResource::class)]
final class DeploymentsResourceTest extends TestCase
{
    public function testListMapsEnumsAndAppendsLimitQuery(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
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
        ]));

        $deps = $this->resource($psr18)->list('app_1', limit: 5);

        self::assertCount(2, $deps);
        self::assertSame(DeploymentAction::Deploy, $deps[0]->action);
        self::assertSame(DeploymentState::Ok, $deps[0]->state);
        self::assertSame('abc123', $deps[0]->commit);
        self::assertSame(DeploymentState::Fail, $deps[1]->state);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/deployments?limit=5',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testCancelHitsDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->cancel('app_1', 'dep_1', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/deployments/dep_1',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    private function resource(RecordingClient $psr18): DeploymentsResource
    {
        return new DeploymentsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
