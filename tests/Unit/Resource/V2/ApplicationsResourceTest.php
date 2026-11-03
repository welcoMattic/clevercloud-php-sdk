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
            'last_deploy' => 42,
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
        self::assertSame(42, $app->lastDeploy);
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

    public function testSetExposedEnvEncodesAsNameToValueObject(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->setExposedEnv('app_1', ['SECRET_TOKEN' => 'abc']);

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/exposed_env',
            $response->getRequestUrl(),
        );
        self::assertSame('{"SECRET_TOKEN":"abc"}', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testSetExposedEnvWithEmptyMapSendsEmptyObject(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->setExposedEnv('app_1', []);

        self::assertSame('{}', $response->getRequestOptions()['body']);
    }

    public function testExposedEnvReadsNameToValueObject(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['SECRET_TOKEN' => 'abc', 'REGION' => 'par']);

        $env = $this->resource($response)->exposedEnv('app_1', 'orga_1');

        self::assertSame(['SECRET_TOKEN' => 'abc', 'REGION' => 'par'], $env);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/exposed_env',
            $response->getRequestUrl(),
        );
    }

    public function testLinkAddonPostsAddonIdAsBareJsonString(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->linkAddon('app_1', 'addon_1', 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/addons',
            $response->getRequestUrl(),
        );
        self::assertSame('"addon_1"', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testTagsListReturnsStringList(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['production', 'critical']);

        $tags = $this->resource($response)->tags('app_1');

        self::assertSame(['production', 'critical'], $tags);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/tags', $response->getRequestUrl());
    }

    public function testSetBuildFlavorPutsFlavorName(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->setBuildFlavor('app_1', 'M', 'orga_1');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/buildflavor',
            $response->getRequestUrl(),
        );
        self::assertSame('{"flavorName":"M"}', $response->getRequestOptions()['body']);
    }

    public function testSetBuildFlavorAcceptsNullToClear(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->setBuildFlavor('app_1', null);

        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/buildflavor', $response->getRequestUrl());
        self::assertSame('{"flavorName":null}', $response->getRequestOptions()['body']);
    }

    public function testDependentsReturnsTypedApplications(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'app_2', 'name' => 'front'],
        ]);

        $dependents = $this->resource($response)->dependents('app_1', 'orga_1');

        self::assertCount(1, $dependents);
        self::assertSame('app_2', $dependents[0]->id);
        self::assertSame('front', $dependents[0]->name);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/dependents',
            $response->getRequestUrl(),
        );
    }

    public function testDependenciesEnvReturnsRawEntries(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['app_id' => 'app_2', 'app_name' => 'api', 'env' => [['name' => 'API_URL', 'value' => 'https://api']]],
        ]);

        $env = $this->resource($response)->dependenciesEnv('app_1');

        self::assertSame('app_2', $env[0]['app_id']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/dependencies/env',
            $response->getRequestUrl(),
        );
    }

    public function testAddonsEnvReturnsRawEntries(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'addon_id' => 'addon_1',
                'addon_name' => 'db',
                'provider_id' => 'postgresql-addon',
                'env' => [['name' => 'POSTGRESQL_ADDON_URI', 'value' => 'postgres://x']],
            ],
        ]);

        $env = $this->resource($response)->addonsEnv('app_1', 'orga_1');

        self::assertSame('addon_1', $env[0]['addon_id']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/addons/env',
            $response->getRequestUrl(),
        );
    }

    public function testReplaceTagsPutsTagList(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['prod', 'team:core']);

        $tags = $this->resource($response)->replaceTags('app_1', ['prod', 'team:core'], 'orga_1');

        self::assertSame(['prod', 'team:core'], $tags);
        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/tags',
            $response->getRequestUrl(),
        );
        self::assertSame('["prod","team:core"]', $response->getRequestOptions()['body']);
    }

    private function resource(MockResponse $response): ApplicationsResource
    {
        return new ApplicationsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
