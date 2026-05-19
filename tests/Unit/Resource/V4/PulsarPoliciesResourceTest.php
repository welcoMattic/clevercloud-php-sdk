<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\PulsarPoliciesResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PulsarPoliciesResource::class)]
final class PulsarPoliciesResourceTest extends TestCase
{
    public function testGetReturnsTypedPolicy(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            'addonId' => 'pulsar_1',
            'namespace' => 'cc/default',
            'retentionTimeInMinutes' => 1440,
            'retentionSizeInMB' => 1024,
        ]));

        $policy = $this->resource($psr18)->get('pulsar_1');

        self::assertSame('pulsar_1', $policy->addonId);
        self::assertSame('cc/default', $policy->namespace);
        self::assertSame(1440, $policy->retentionTimeInMinutes);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/policy',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testUpdatePutsJson(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, ['addonId' => 'pulsar_1']));

        $this->resource($psr18)->update('pulsar_1', ['retentionTimeInMinutes' => 60]);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('PUT', $psr18->lastRequest->getMethod());
        self::assertSame(
            '{"retentionTimeInMinutes":60}',
            (string) $psr18->lastRequest->getBody(),
        );
    }

    public function testDeleteHitsDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->delete('pulsar_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/policy',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    private function resource(RecordingClient $psr18): PulsarPoliciesResource
    {
        return new PulsarPoliciesResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
