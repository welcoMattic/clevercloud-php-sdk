<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\ApplicationsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplicationsResource::class)]
final class ApplicationsResourceTest extends TestCase
{
    public function testListForCurrentUserHitsSelfPath(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['id' => 'app_1', 'name' => 'app-one', 'zone' => 'par', 'state' => 'SHOULD_BE_UP'],
            ['id' => 'app_2', 'name' => 'app-two'],
        ]));

        $apps = $this->resource($psr18)->list();

        self::assertCount(2, $apps);
        self::assertSame('app_1', $apps[0]->id);
        self::assertSame('app-one', $apps[0]->name);
        self::assertSame('par', $apps[0]->zone);
        self::assertSame('SHOULD_BE_UP', $apps[0]->state);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications', (string) $psr18->lastRequest->getUri());
    }

    public function testListForOrganisationHitsOrganisationPath(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->list('orga_42');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_42/applications', (string) $psr18->lastRequest->getUri());
    }

    public function testGetReturnsTypedApplication(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            'id' => 'app_1',
            'name' => 'app-one',
            'description' => 'demo',
            'branch' => 'main',
            'ownerId' => 'user_xxx',
            'archived' => false,
            'favourite' => true,
            'creationDate' => 1_700_000_000_000,
            'vhosts' => [['fqdn' => 'demo.example.com']],
            'instance' => ['type' => 'node', 'version' => '20'],
        ]));

        $app = $this->resource($psr18)->get('app_1');

        self::assertSame('app_1', $app->id);
        self::assertSame('main', $app->branch);
        self::assertSame('user_xxx', $app->ownerId);
        self::assertFalse($app->archived);
        self::assertTrue($app->favourite);
        self::assertSame(1_700_000_000_000, $app->creationDate);
        self::assertCount(1, $app->vhosts);
        self::assertSame('demo.example.com', $app->vhosts[0]->fqdn);
        self::assertSame(['type' => 'node', 'version' => '20'], $app->instance);
    }

    public function testCreatePostsJsonBody(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(201, ['id' => 'app_new', 'name' => 'fresh']));

        $this->resource($psr18)->create([
            'name' => 'fresh',
            'deploy' => 'git',
            'instanceType' => 'node',
            'zone' => 'par',
        ], 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/applications', (string) $psr18->lastRequest->getUri());
        self::assertSame(
            '{"name":"fresh","deploy":"git","instanceType":"node","zone":"par"}',
            (string) $psr18->lastRequest->getBody(),
        );
    }

    public function testRestartHitsInstancesPost(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->restart('app_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/instances', (string) $psr18->lastRequest->getUri());
    }

    public function testRestartWithoutCacheAppendsQuery(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->restart('app_1', withoutCache: true);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/instances?useCache=no',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testStopHitsInstancesDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->stop('app_1', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/instances',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testSetBranchSendsPutWithBranch(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->setBranch('app_1', 'feature/x');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('PUT', $psr18->lastRequest->getMethod());
        self::assertStringEndsWith('/applications/app_1/branch', $psr18->lastRequest->getUri()->getPath());
        self::assertSame('{"branch":"feature/x"}', (string) $psr18->lastRequest->getBody());
    }

    private function resource(RecordingClient $psr18): ApplicationsResource
    {
        return new ApplicationsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
