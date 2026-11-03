<?php

namespace CleverCloud\Sdk\Tests\Unit\Http;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Http\UriBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UriBuilder::class)]
#[CoversClass(Configuration::class)]
final class UriBuilderTest extends TestCase
{
    /**
     * @return iterable<string, array{ApiVersion, string, string}>
     */
    public static function versions(): iterable
    {
        yield 'v2' => [ApiVersion::V2, '/self', 'https://api.clever-cloud.com/v2/self'];
        yield 'v4' => [ApiVersion::V4, '/products/zones', 'https://api.clever-cloud.com/v4/products/zones'];
        yield 'bridge' => [ApiVersion::Bridge, '/api-tokens', 'https://api-bridge.clever-cloud.com/api-tokens'];
        yield 'root' => [
            ApiVersion::Root,
            '/organisations/orga_1/logs/exports',
            'https://api.clever-cloud.com/organisations/orga_1/logs/exports',
        ];
    }

    #[DataProvider('versions')]
    public function testPrefixesPathWithVersionBaseUrl(ApiVersion $version, string $path, string $expected): void
    {
        $builder = new UriBuilder(new Configuration(), new Psr17Factory());

        self::assertSame($expected, (string) $builder->build($version, $path));
    }

    public function testRootBaseUrlIsConfigurable(): void
    {
        $builder = new UriBuilder(new Configuration(rootBaseUrl: 'https://api.example.test/'), new Psr17Factory());

        self::assertSame(
            'https://api.example.test/networkgroup/configuration',
            (string) $builder->build(ApiVersion::Root, 'networkgroup/configuration'),
        );
    }
}
