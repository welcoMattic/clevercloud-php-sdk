<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\ApplicationsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ApplicationsResource::class)]
final class ApplicationsResourceTest extends TestCase
{
    public function testListForCurrentUserHitsSelfPath(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'app_1', 'name' => 'app-one', 'zone' => 'par', 'state' => 'SHOULD_BE_UP'],
            ['id' => 'app_2', 'name' => 'app-two'],
        ]);

        $apps = $this->resource($response)->list();

        self::assertCount(2, $apps);
        self::assertSame('app_1', $apps[0]->id);
        self::assertSame('app-one', $apps[0]->name);
        self::assertSame('par', $apps[0]->zone);
        self::assertSame('SHOULD_BE_UP', $apps[0]->state);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications', $response->getRequestUrl());
    }

    public function testListForOrganisationHitsOrganisationPath(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->list('orga_42');

        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_42/applications', $response->getRequestUrl());
    }

    public function testGetReturnsTypedApplication(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
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
        ]);

        $app = $this->resource($response)->get('app_1');

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
        $response = ResourceFactory::jsonResponse(201, ['id' => 'app_new', 'name' => 'fresh']);

        $this->resource($response)->create([
            'name' => 'fresh',
            'deploy' => 'git',
            'instanceType' => 'node',
            'zone' => 'par',
        ], 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/applications', $response->getRequestUrl());
        self::assertSame(
            '{"name":"fresh","deploy":"git","instanceType":"node","zone":"par"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testRestartHitsInstancesPost(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->restart('app_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/instances', $response->getRequestUrl());
        self::assertSame('{}', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testRestartWithoutCacheAppendsQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->restart('app_1', withoutCache: true);

        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/instances?useCache=no',
            $response->getRequestUrl(),
        );
    }

    public function testStopHitsInstancesDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->stop('app_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/instances',
            $response->getRequestUrl(),
        );
    }

    public function testSetBranchSendsPutWithBranch(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->setBranch('app_1', 'feature/x');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertStringEndsWith('/applications/app_1/branch', $response->getRequestUrl());
        self::assertSame('{"branch":"feature/x"}', $response->getRequestOptions()['body']);
    }

    public function testBranchesReturnsListOfNames(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['main', 'develop', 'feature/x']);

        $branches = $this->resource($response)->branches('app_1', 'orga_1');

        self::assertSame(['main', 'develop', 'feature/x'], $branches);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/branches',
            $response->getRequestUrl(),
        );
    }

    public function testDeployPostsInstancesEndpointWithCommit(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->deploy('app_1', 'abc123', 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/instances?commit=abc123',
            $response->getRequestUrl(),
        );
        self::assertSame('{}', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testDeployWithoutCommitOmitsQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->deploy('app_1');

        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/instances',
            $response->getRequestUrl(),
        );
    }

    public function testAddDependencyHitsPut(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->addDependency('app_1', 'app_2', 'orga_1');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/dependencies/app_2',
            $response->getRequestUrl(),
        );
    }

    public function testSetExposedEnvEncodesAsList(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->setExposedEnv('app_1', ['SECRET_TOKEN' => 'abc']);

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/exposed_env',
            $response->getRequestUrl(),
        );
        self::assertSame(
            '[{"name":"SECRET_TOKEN","value":"abc"}]',
            $response->getRequestOptions()['body'],
        );
    }

    public function testTagsListReturnsStringList(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['production', 'critical']);

        $tags = $this->resource($response)->tags('app_1');

        self::assertSame(['production', 'critical'], $tags);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/tags', $response->getRequestUrl());
    }

    private function resource(MockResponse $response): ApplicationsResource
    {
        return new ApplicationsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
