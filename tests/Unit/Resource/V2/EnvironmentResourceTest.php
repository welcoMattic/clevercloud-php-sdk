<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\EnvironmentResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvironmentResource::class)]
final class EnvironmentResourceTest extends TestCase
{
    public function testListFlattensNameValueObjectsIntoMap(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['name' => 'NODE_ENV', 'value' => 'production'],
            ['name' => 'DEBUG', 'value' => '0'],
        ]));

        $env = $this->resource($psr18)->list('app_1');

        self::assertSame(['NODE_ENV' => 'production', 'DEBUG' => '0'], $env);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env', (string) $psr18->lastRequest->getUri());
    }

    public function testGetReadsFromFlattenedList(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['name' => 'FOO', 'value' => 'bar'],
        ]));

        $value = $this->resource($psr18)->get('app_1', 'FOO');

        self::assertSame('bar', $value);
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        self::assertNull($this->resource($psr18)->get('app_1', 'MISSING'));
    }

    public function testSetPutsJsonWithName(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->set('app_1', 'API_KEY', 'secret', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('PUT', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/env/API_KEY',
            (string) $psr18->lastRequest->getUri(),
        );
        self::assertSame('{"name":"API_KEY","value":"secret"}', (string) $psr18->lastRequest->getBody());
    }

    public function testSetManyEncodesAsList(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->setMany('app_1', ['NODE_ENV' => 'production', 'DEBUG' => '0']);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('PUT', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env', (string) $psr18->lastRequest->getUri());
        self::assertSame(
            '[{"name":"NODE_ENV","value":"production"},{"name":"DEBUG","value":"0"}]',
            (string) $psr18->lastRequest->getBody(),
        );
    }

    public function testRemoveHitsDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->remove('app_1', 'FOO');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/applications/app_1/env/FOO', (string) $psr18->lastRequest->getUri());
    }

    private function resource(RecordingClient $psr18): EnvironmentResource
    {
        return new EnvironmentResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
