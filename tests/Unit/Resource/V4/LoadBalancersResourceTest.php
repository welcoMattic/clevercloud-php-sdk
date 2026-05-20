<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\LoadBalancersResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(LoadBalancersResource::class)]
final class LoadBalancersResourceTest extends TestCase
{
    public function testListReturnsTypedLoadBalancers(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'lb_default',
                'kind' => 'DEFAULT',
                'isDefault' => true,
                'dns' => ['hostname' => 'app.cleverapps.io', 'aRecords' => ['1.2.3.4']],
            ],
        ]);

        $lbs = $this->resource($response)->list('app_1');

        self::assertCount(1, $lbs);
        self::assertSame('lb_default', $lbs[0]->id);
        self::assertTrue($lbs[0]->isDefault);
        self::assertSame(['hostname' => 'app.cleverapps.io', 'aRecords' => ['1.2.3.4']], $lbs[0]->dns);
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/self/applications/app_1/load-balancers',
            $response->getRequestUrl(),
        );
    }

    public function testDnsInfoHitsDefaultDns(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['hostname' => 'app.cleverapps.io']);

        $dns = $this->resource($response)->dnsInfo('app_1', 'orga_1');

        self::assertSame(['hostname' => 'app.cleverapps.io'], $dns);
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/organisations/orga_1/applications/app_1/load-balancers/default/dns',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): LoadBalancersResource
    {
        return new LoadBalancersResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
