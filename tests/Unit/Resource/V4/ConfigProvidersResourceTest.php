<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\ConfigProvidersResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ConfigProvidersResource::class)]
final class ConfigProvidersResourceTest extends TestCase
{
    private const string BASE = 'https://api.clever-cloud.com/v4/addon-providers/config-provider/addons/config_1';

    public function testGetMapsAddonAndEnvAsMap(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'config_1',
            'creation_date' => '2026-01-02T03:04:05Z',
            'owner_id' => 'orga_1',
            'env' => [
                ['name' => 'FOO', 'value' => 'bar'],
                ['name' => 'BAZ', 'value' => 'qux'],
            ],
        ]);

        $provider = $this->resource($response)->get('config_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE, $response->getRequestUrl());
        self::assertSame('config_1', $provider->id);
        self::assertSame('2026-01-02T03:04:05Z', $provider->creationDate);
        self::assertSame('orga_1', $provider->ownerId);
        self::assertSame(['FOO' => 'bar', 'BAZ' => 'qux'], $provider->env);
    }

    public function testEnvReturnsNameValueMap(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'FOO', 'value' => 'bar'],
        ]);

        $env = $this->resource($response)->env('config_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/env', $response->getRequestUrl());
        self::assertSame(['FOO' => 'bar'], $env);
    }

    public function testReplaceEnvPutsNameValueList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'FOO', 'value' => 'bar'],
            ['name' => 'BAZ', 'value' => ''],
        ]);

        $env = $this->resource($response)->replaceEnv('config_1', ['FOO' => 'bar', 'BAZ' => '']);

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(self::BASE.'/env', $response->getRequestUrl());
        self::assertSame(
            '[{"name":"FOO","value":"bar"},{"name":"BAZ","value":""}]',
            $response->getRequestOptions()['body'],
        );
        self::assertSame(['FOO' => 'bar', 'BAZ' => ''], $env);
    }

    public function testReplaceEnvWithEmptyMapSendsEmptyList(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $env = $this->resource($response)->replaceEnv('config_1', []);

        self::assertSame('[]', $response->getRequestOptions()['body']);
        self::assertSame([], $env);
    }

    private function resource(MockResponse $response): ConfigProvidersResource
    {
        return new ConfigProvidersResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
