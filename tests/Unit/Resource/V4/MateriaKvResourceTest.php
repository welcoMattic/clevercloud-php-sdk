<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\MateriaKvResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(MateriaKvResource::class)]
final class MateriaKvResourceTest extends TestCase
{
    public function testProviderInformationHitsKvProvider(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'providerId' => 'kv',
            'defaultDedicatedVersion' => '',
            'clusters' => [
                ['id' => 'materia_par', 'label' => 'Paris', 'zone' => 'par', 'version' => '1.0'],
            ],
            'dedicated' => [],
        ]);

        $info = $this->resource($response)->providerInformation();

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v4/addon-providers/kv', $response->getRequestUrl());
        self::assertSame('kv', $info->providerId);
        self::assertSame('materia_par', $info->clusters[0]->id);
        self::assertSame([], $info->clusters[0]->features);
    }

    public function testGetReturnsTypedDatabase(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'kv_1',
            'clusterId' => 'materia_par',
            'ownerId' => 'orga_1',
            'kind' => 'KV',
            'plan' => 'BASE',
            'host' => 'materiadb.example',
            'port' => 6379,
            'token' => 'secret',
            'tokenId' => 'token_1',
            'status' => 'PROVISIONED',
        ]);

        $db = $this->resource($response)->get('orga_1', 'kv_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/materia/organisations/orga_1/materia/databases/kv_1',
            $response->getRequestUrl(),
        );
        self::assertSame('kv_1', $db->id);
        self::assertSame('materia_par', $db->clusterId);
        self::assertSame('orga_1', $db->ownerId);
        self::assertSame('KV', $db->kind);
        self::assertSame('BASE', $db->plan);
        self::assertSame('materiadb.example', $db->host);
        self::assertSame(6379, $db->port);
        self::assertSame('secret', $db->token);
        self::assertSame('token_1', $db->tokenId);
        self::assertSame('PROVISIONED', $db->status);
    }

    private function resource(MockResponse $response): MateriaKvResource
    {
        return new MateriaKvResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
