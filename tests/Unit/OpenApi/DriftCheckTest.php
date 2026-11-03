<?php

namespace CleverCloud\Sdk\Tests\Unit\OpenApi;

use CleverCloud\Sdk\Tests\OpenApi\DriftCheck;
use CleverCloud\Sdk\Tests\OpenApi\SdkCall;
use CleverCloud\Sdk\Tests\OpenApi\SpecExtract;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DriftCheckTest extends TestCase
{
    public function testMatchingSemantics(): void
    {
        $check = new DriftCheck(
            self::extract([
                'GET /v2/self/applications/{appId}/vhosts/{domain}',
                'GET /v2/self/applications/{appId}/vhosts/favourite',
                'POST /v2/self/mfa/{kind}',
                'GET /organisations/{orgId}/logs/exports',
                'DELETE /v4/never/called',
            ]),
            [
                new SdkCall('GET', '/v2/self/applications/{}/vhosts/favourite', 'A::favourite'),
                new SdkCall('POST', '/v2/self/mfa/backupcodes', 'A::backup'),
                new SdkCall('GET', '/v4/organisations/{}/logs/exports', 'A::exports'),
                new SdkCall('GET', '/v2/self/applications/{}/vhosts', 'A::list'),
                new SdkCall('PUT', '/v2/self/applications/{}/vhosts/favourite', 'A::put'),
                new SdkCall('GET', '/v2/products/zones', 'A::zones'),
            ],
            [
                ['method' => '*', 'pattern' => '/v2/products*', 'reason' => 'absent family'],
                ['method' => 'GET', 'pattern' => '/v2/unused*', 'reason' => 'stale'],
            ],
        );

        self::assertSame(
            ['GET /v2/self/applications/{}/vhosts/favourite' => 'GET /v2/self/applications/{appId}/vhosts/favourite', 'POST /v2/self/mfa/backupcodes' => 'POST /v2/self/mfa/{kind}'],
            $check->matched,
        );
        self::assertSame(['POST /v2/self/mfa/backupcodes' => 'POST /v2/self/mfa/{kind}'], $check->literalBindings);
        self::assertSame(['GET /v4/organisations/{}/logs/exports' => 'GET /organisations/{orgId}/logs/exports'], $check->softMatched);
        self::assertSame(['GET /v2/products/zones' => 'absent family'], $check->allowlisted);
        self::assertSame(
            ['GET /v2/self/applications/{}/vhosts' => ['A::list'], 'PUT /v2/self/applications/{}/vhosts/favourite' => ['A::put']],
            $check->missing,
        );
        self::assertSame(['GET /v2/unused*'], $check->staleAllowlist);

        $uncovered = array_map(static fn (array $operation): string => $operation['method'].' '.$operation['path'], $check->uncovered);
        self::assertSame(['GET /v2/self/applications/{appId}/vhosts/{domain}', 'DELETE /v4/never/called'], $uncovered);
    }

    public function testSdkParameterDoesNotMatchSpecLiteral(): void
    {
        $check = new DriftCheck(
            self::extract(['GET /v2/self/applications/{appId}/vhosts/favourite']),
            [new SdkCall('GET', '/v2/self/applications/{}/vhosts/{}', 'A::get')],
            [],
        );

        self::assertSame(['GET /v2/self/applications/{}/vhosts/{}' => ['A::get']], $check->missing);
    }

    public function testAllowlistDoesNotHideDocumentedCalls(): void
    {
        $check = new DriftCheck(
            self::extract(['GET /v2/products/zones']),
            [new SdkCall('GET', '/v2/products/zones', 'A::zones')],
            [['method' => '*', 'pattern' => '/v2/products*', 'reason' => 'absent family']],
        );

        self::assertSame(['GET /v2/products/zones' => 'GET /v2/products/zones'], $check->matched);
        self::assertSame(['* /v2/products*'], $check->staleAllowlist);
    }

    /**
     * @param list<string> $operations "METHOD path"
     */
    private static function extract(array $operations): SpecExtract
    {
        $paths = [];
        foreach ($operations as $index => $operation) {
            [$method, $path] = explode(' ', $operation, 2);
            $paths[$path][strtolower($method)] = ['operationId' => 'op'.$index, 'tags' => ['T']];
        }

        return SpecExtract::fromRawSpec(json_encode(['openapi' => '3.1.0', 'paths' => $paths], JSON_THROW_ON_ERROR), '2026-01-01');
    }
}
