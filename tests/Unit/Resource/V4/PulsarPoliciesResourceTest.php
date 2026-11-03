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
    public function testGetMapsTheSpecShape(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'retentionPolicies' => ['durationInMinutes' => 1440, 'sizeInBytes' => 1073741824],
            'offloadPolicies' => null,
        ]);

        $policy = $this->resource($response)->get('pulsar_1');

        self::assertSame('pulsar_1', $policy->addonId);
        self::assertNotNull($policy->retentionPolicies);
        self::assertSame(1440, $policy->retentionPolicies->durationInMinutes);
        self::assertSame(1073741824, $policy->retentionPolicies->sizeInBytes);
        self::assertNull($policy->offloadPolicies);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/storage-policies',
            $response->getRequestUrl(),
        );
    }

    public function testGetMapsAnEmptyPolicy(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $policy = $this->resource($response)->get('pulsar_1');

        self::assertSame('pulsar_1', $policy->addonId);
        self::assertNull($policy->retentionPolicies);
        self::assertNull($policy->offloadPolicies);
    }

    public function testUpdatePatchesJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'retentionPolicies' => ['durationInMinutes' => 60, 'sizeInBytes' => null],
        ]);

        $policy = $this->resource($response)->update('pulsar_1', [
            'retentionPolicies' => ['durationInMinutes' => 60],
        ]);

        self::assertSame('pulsar_1', $policy->addonId);
        self::assertNotNull($policy->retentionPolicies);
        self::assertSame(60, $policy->retentionPolicies->durationInMinutes);
        self::assertNull($policy->retentionPolicies->sizeInBytes);

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar/addons/pulsar_1/storage-policies',
            $response->getRequestUrl(),
        );
        self::assertSame(
            '{"retentionPolicies":{"durationInMinutes":60}}',
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
