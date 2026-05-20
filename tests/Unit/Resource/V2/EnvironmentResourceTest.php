<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\EnvironmentResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(EnvironmentResource::class)]
final class EnvironmentResourceTest extends TestCase
{
    public function testListFlattensNameValueObjectsIntoMap(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'NODE_ENV', 'value' => 'production'],
            ['name' => 'DEBUG', 'value' => '0'],
        ]);

        $env = $this->resource($response)->list('app_1');

        self::assertSame(['NODE_ENV' => 'production', 'DEBUG' => '0'], $env);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env', $response->getRequestUrl());
    }

    public function testGetReadsFromFlattenedList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'FOO', 'value' => 'bar'],
        ]);

        $value = $this->resource($response)->get('app_1', 'FOO');

        self::assertSame('bar', $value);
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        self::assertNull($this->resource($response)->get('app_1', 'MISSING'));
    }

    public function testSetPutsJsonWithName(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->set('app_1', 'API_KEY', 'secret', 'orga_1');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/env/API_KEY',
            $response->getRequestUrl(),
        );
        self::assertSame('{"name":"API_KEY","value":"secret"}', $response->getRequestOptions()['body']);
    }

    public function testSetManyEncodesAsList(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->setMany('app_1', ['NODE_ENV' => 'production', 'DEBUG' => '0']);

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env', $response->getRequestUrl());
        self::assertSame(
            '[{"name":"NODE_ENV","value":"production"},{"name":"DEBUG","value":"0"}]',
            $response->getRequestOptions()['body'],
        );
    }

    public function testRemoveHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->remove('app_1', 'FOO');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env/FOO', $response->getRequestUrl());
    }

    private function resource(MockResponse $response): EnvironmentResource
    {
        return new EnvironmentResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
