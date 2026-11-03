<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\AddonProvidersResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(AddonProvidersResource::class)]
final class AddonProvidersResourceTest extends TestCase
{
    public function testGetAddonReturnsProviderPayload(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'postgresql_1', 'plan' => 'xs_sml']);

        $addon = $this->resource($response)->getAddon('postgresql-addon', 'postgresql_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/postgresql-addon/addons/postgresql_1',
            $response->getRequestUrl(),
        );
        self::assertSame(['id' => 'postgresql_1', 'plan' => 'xs_sml'], $addon);
    }

    private function resource(MockResponse $response): AddonProvidersResource
    {
        return new AddonProvidersResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
