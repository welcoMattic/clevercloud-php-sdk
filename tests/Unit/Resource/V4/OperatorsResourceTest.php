<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\Operator\KeycloakResource;
use CleverCloud\Sdk\Resource\V4\Operator\MatomoResource;
use CleverCloud\Sdk\Resource\V4\OperatorsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeycloakResource::class)]
#[CoversClass(MatomoResource::class)]
#[CoversClass(OperatorsResource::class)]
final class OperatorsResourceTest extends TestCase
{
    public function testKeycloakListRoutesUnderKeycloakSlug(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['id' => 'op_1', 'name' => 'idp-prod', 'region' => 'par'],
        ]));

        $ops = $this->facade($psr18)->keycloak->list('orga_1');

        self::assertCount(1, $ops);
        self::assertSame('op_1', $ops[0]->id);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/operators/keycloak/organisations/orga_1',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testMatomoCreatePostsBody(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(201, ['id' => 'op_new', 'name' => 'analytics']));

        $this->facade($psr18)->matomo->create(['name' => 'analytics', 'region' => 'par']);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/operators/matomo/self',
            (string) $psr18->lastRequest->getUri(),
        );
        self::assertSame('{"name":"analytics","region":"par"}', (string) $psr18->lastRequest->getBody());
    }

    public function testRebootPostsToRebootSubpath(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->facade($psr18)->keycloak->reboot('op_1', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/operators/keycloak/organisations/orga_1/op_1/reboot',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testRebuildPostsToRebuildSubpath(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->facade($psr18)->matomo->rebuild('op_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/operators/matomo/self/op_1/rebuild',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testSubResourcesAreMemoised(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));
        $facade = $this->facade($psr18);

        self::assertSame($facade->keycloak, $facade->keycloak);
        self::assertSame($facade->matomo, $facade->matomo);
    }

    private function facade(RecordingClient $psr18): OperatorsResource
    {
        return new OperatorsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
