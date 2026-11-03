<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\SelfResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(SelfResource::class)]
final class SelfResourceTest extends TestCase
{
    public function testRegenerateMfaBackupCodesPostsUnderTheMfaKind(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['backupCodes' => ['code-1', 'code-2']]);

        $codes = $this->resource($response)->regenerateMfaBackupCodes();

        self::assertSame(['code-1', 'code-2'], $codes);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/mfa/TOTP/backupcodes',
            $response->getRequestUrl(),
        );
    }

    public function testRegenerateMfaBackupCodesHonoursExplicitKind(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['backupCodes' => []]);

        $codes = $this->resource($response)->regenerateMfaBackupCodes('totp');

        self::assertSame([], $codes);
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/mfa/totp/backupcodes',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): SelfResource
    {
        return new SelfResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
