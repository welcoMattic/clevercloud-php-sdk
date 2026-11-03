<?php

namespace CleverCloud\Sdk\Tests\Unit\OpenApi;

use CleverCloud\Sdk\Tests\OpenApi\SpecExtract;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SpecExtractTest extends TestCase
{
    public function testExtractsOperationsAndFlattensSchemas(): void
    {
        $raw = json_encode([
            'openapi' => '3.1.0',
            'security' => [['oauth1' => []]],
            'paths' => [
                '/v2/self' => [
                    'parameters' => [],
                    'get' => ['operationId' => 'getSelf', 'tags' => ['Self'], 'description' => 'Prose is dropped.'],
                    'put' => ['operationId' => 'putSelf', 'tags' => ['Self'], 'security' => [['oauth1' => []], ['biscuit' => []]]],
                ],
                '/public' => ['get' => ['operationId' => 'pub', 'security' => []]],
            ],
            'components' => ['schemas' => [
                'Base' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']]],
                'Child' => ['allOf' => [
                    ['$ref' => '#/components/schemas/Base'],
                    ['type' => 'object', 'properties' => [
                        'tags' => ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
                        'flavor' => ['oneOf' => [['type' => 'null'], ['$ref' => '#/components/schemas/Base']]],
                        'env' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
                    ]],
                ]],
                'Kind' => ['type' => 'string', 'enum' => ['a', 'b']],
            ]],
        ], JSON_THROW_ON_ERROR);

        $extract = SpecExtract::fromRawSpec($raw, '2026-01-01');

        self::assertSame(hash('sha256', $raw), $extract->sha256);
        self::assertSame([
            ['method' => 'GET', 'path' => '/public', 'operationId' => 'pub', 'tags' => [], 'security' => []],
            ['method' => 'GET', 'path' => '/v2/self', 'operationId' => 'getSelf', 'tags' => ['Self'], 'security' => ['oauth1']],
            ['method' => 'PUT', 'path' => '/v2/self', 'operationId' => 'putSelf', 'tags' => ['Self'], 'security' => ['oauth1', 'biscuit']],
        ], $extract->operations);
        self::assertSame(
            ['env' => 'map<string>', 'flavor' => 'null|#Base', 'id' => 'string', 'tags' => 'string[]|null'],
            $extract->schemas['Child']['properties'],
        );
        self::assertSame(['type' => 'string', 'properties' => []], $extract->schemas['Kind']);
        self::assertStringNotContainsString('Prose', $extract->toJson());
    }

    public function testJsonRoundTrips(): void
    {
        $extract = SpecExtract::load();
        $path = tempnam(sys_get_temp_dir(), 'extract');
        self::assertIsString($path);
        file_put_contents($path, $extract->toJson());

        try {
            self::assertEquals($extract, SpecExtract::load($path));
            self::assertSame($extract->toJson(), (string) file_get_contents(SpecExtract::defaultPath()));
        } finally {
            unlink($path);
        }
    }

    public function testBuildRefIsWrittenOnlyWhenKnown(): void
    {
        $raw = json_encode(['openapi' => '3.1.0', 'paths' => []], JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('"buildRef"', SpecExtract::fromRawSpec($raw, '2026-01-01')->toJson());
        self::assertStringContainsString(
            '"buildRef": "abc123",',
            SpecExtract::fromRawSpec($raw, '2026-01-01', buildRef: 'abc123')->toJson(),
        );
        self::assertNotNull(SpecExtract::load()->buildRef);
    }
}
