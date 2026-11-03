<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\OrchestrationResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(OrchestrationResource::class)]
final class OrchestrationResourceTest extends TestCase
{
    public function testInstancesResolvesThePersonalOrganisation(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, [['id' => 'instance_1']]);

        $instances = $this->resource($self, $response)->instances('app_1');

        self::assertSame([['id' => 'instance_1']], $instances);
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/orchestration/organisations/user_1/applications/app_1/instances',
            $response->getRequestUrl(),
        );
    }

    public function testDeploymentsMapsTheV4PayloadAndPassesPaging(): void
    {
        $response = ResourceFactory::jsonResponse(200, [self::deploymentPayload()]);

        $deployments = $this->resource($response)->deployments('app_1', 'orga_1', limit: 1, offset: 2);

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/orchestration/organisations/orga_1/applications/app_1/deployments?limit=1&offset=2',
            $response->getRequestUrl(),
        );
        self::assertCount(1, $deployments);
        $deployment = $deployments[0];
        self::assertSame('deployment_1', $deployment->id);
        self::assertSame('orga_1', $deployment->ownerId);
        self::assertSame('app_1', $deployment->applicationId);
        self::assertSame('2026-11-03T22:05:42.059Z', $deployment->startDate);
        self::assertSame('SUCCEEDED', $deployment->state);
        self::assertCount(2, $deployment->steps);
        self::assertSame('QUEUED', $deployment->steps[0]->state);
        self::assertSame('2026-11-03T22:05:42.059Z', $deployment->steps[0]->date);
        self::assertSame('SUCCEEDED', $deployment->steps[1]->state);
        self::assertSame(['commitId' => 'cdb6f2e', 'previousCommitId' => 'a1b2c3d'], $deployment->version);
        self::assertSame('DEPLOY', $deployment->origin['action']);
        self::assertSame(['!bigberta'], $deployment->origin['constraints']);
        self::assertFalse($deployment->hasDedicatedBuild);
    }

    public function testGetDeploymentResolvesThePersonalOrganisation(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, self::deploymentPayload());

        $deployment = $this->resource($self, $response)->getDeployment('app_1', 'deployment_1');

        self::assertSame('deployment_1', $deployment->id);
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/orchestration/organisations/user_1/applications/app_1/deployments/deployment_1',
            $response->getRequestUrl(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function deploymentPayload(): array
    {
        return [
            'id' => 'deployment_1',
            'ownerId' => 'orga_1',
            'applicationId' => 'app_1',
            'startDate' => '2026-11-03T22:05:42.059Z',
            'state' => 'SUCCEEDED',
            'steps' => [
                ['state' => 'QUEUED', 'date' => '2026-11-03T22:05:42.059Z'],
                ['state' => 'SUCCEEDED', 'date' => '2026-11-03T22:07:10.120Z'],
            ],
            'version' => ['commitId' => 'cdb6f2e', 'previousCommitId' => 'a1b2c3d'],
            'origin' => [
                'action' => 'DEPLOY',
                'cause' => 'git push',
                'source' => 'apps',
                'authorId' => 'user_1',
                'constraints' => ['!bigberta'],
                'priority' => 'USER',
            ],
            'hasDedicatedBuild' => false,
        ];
    }

    private function resource(MockResponse ...$responses): OrchestrationResource
    {
        return new OrchestrationResource(ResourceFactory::http(new MockHttpClient($responses)), ResourceFactory::mapper());
    }
}
