<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Model\Enum\DrainKind;
use CleverCloud\Sdk\Model\Enum\DrainType;
use CleverCloud\Sdk\Resource\V4\DrainsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(DrainsResource::class)]
final class DrainsResourceTest extends TestCase
{
    private const string BASE = 'https://api.clever-cloud.com/v4/drains/organisations/orga_1/resources/app_1/drains';

    public function testListResolvesThePersonalOrganisation(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, [self::drainPayload()]);

        $drains = $this->resource($self, $response)->list('app_1');

        self::assertCount(1, $drains);
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/drains/organisations/user_1/resources/app_1/drains',
            $response->getRequestUrl(),
        );
    }

    public function testGetMapsTheV4Payload(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::drainPayload());

        $drain = $this->resource($response)->get('app_1', 'drain_1', 'orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/drain_1', $response->getRequestUrl());
        self::assertSame('drain_1', $drain->id);
        self::assertSame('app_1', $drain->resourceId);
        self::assertSame('LOG', $drain->kind);
        self::assertSame('ENABLED', $drain->status['status']);
        self::assertSame(['type' => 'DATADOG', 'url' => 'https://http-intake.logs.datadoghq.eu/v1/input/key'], $drain->recipient);
        self::assertSame('RUNNING', $drain->execution['status']);
    }

    public function testCreateSendsKindAndRecipient(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::drainPayload());

        $drain = $this->resource($response)->create(
            'app_1',
            DrainType::RawHttp,
            'https://logs.example.com',
            ['username' => 'u', 'password' => 'p'],
            'orga_1',
        );

        self::assertSame('drain_1', $drain->id);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE, $response->getRequestUrl());
        self::assertSame(
            '{"kind":"LOG","recipient":{"type":"RAW_HTTP","url":"https://logs.example.com","username":"u","password":"p"}}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testCreateAccessLogDrainSkippingTheCheck(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::drainPayload());

        $this->resource($response)->create(
            'app_1',
            DrainType::SyslogTcp,
            'tcp://logs.example.com:514',
            organisationId: 'orga_1',
            kind: DrainKind::AccessLog,
            skipCheck: true,
        );

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'?skipCheck=true', $response->getRequestUrl());
        self::assertSame(
            '{"kind":"ACCESSLOG","recipient":{"type":"SYSLOG_TCP","url":"tcp://logs.example.com:514"}}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteHitsTheDrain(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('app_1', 'drain_1', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/drain_1', $response->getRequestUrl());
    }

    public function testEnablePutsEnable(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->enable('app_1', 'drain_1', 'orga_1');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(self::BASE.'/drain_1/enable', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    public function testDisablePutsDisable(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->disable('app_1', 'drain_1', 'orga_1');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(self::BASE.'/drain_1/disable', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    public function testRestartPatchesResetCursor(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::drainPayload());

        $this->resource($response)->restart('app_1', 'drain_1', 'orga_1');

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame(self::BASE.'/drain_1/reset-cursor', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private static function drainPayload(): array
    {
        return [
            'id' => 'drain_1',
            'resourceId' => 'app_1',
            'kind' => 'LOG',
            'status' => ['date' => '2026-11-03T10:00:00Z', 'status' => 'ENABLED'],
            'recipient' => ['type' => 'DATADOG', 'url' => 'https://http-intake.logs.datadoghq.eu/v1/input/key'],
            'execution' => ['status' => 'RUNNING'],
        ];
    }

    private function resource(MockResponse ...$responses): DrainsResource
    {
        return new DrainsResource(ResourceFactory::http(new MockHttpClient($responses)), ResourceFactory::mapper());
    }
}
