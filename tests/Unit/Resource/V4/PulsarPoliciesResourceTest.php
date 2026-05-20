<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\PulsarPoliciesResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(PulsarPoliciesResource::class)]
final class PulsarPoliciesResourceTest extends TestCase
{
    public function testGetReturnsTypedPolicy(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'addonId' => 'pulsar_1',
            'namespace' => 'cc/default',
            'retentionTimeInMinutes' => 1440,
            'retentionSizeInMB' => 1024,
        ]);

        $policy = $this->resource($response)->get('pulsar_1');

        self::assertSame('pulsar_1', $policy->addonId);
        self::assertSame('cc/default', $policy->namespace);
        self::assertSame(1440, $policy->retentionTimeInMinutes);
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/storage-policies',
            $response->getRequestUrl(),
        );
    }

    public function testUpdatePatchesJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['addonId' => 'pulsar_1']);

        $this->resource($response)->update('pulsar_1', ['retentionTimeInMinutes' => 60]);

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/storage-policies',
            $response->getRequestUrl(),
        );
        self::assertSame(
            '{"retentionTimeInMinutes":60}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testResetHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->reset('pulsar_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/storage-policies',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): PulsarPoliciesResource
    {
        return new PulsarPoliciesResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
