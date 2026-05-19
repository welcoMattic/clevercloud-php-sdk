<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\LoadBalancersResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoadBalancersResource::class)]
final class LoadBalancersResourceTest extends TestCase
{
    public function testListReturnsTypedLoadBalancers(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            [
                'id' => 'lb_default',
                'kind' => 'DEFAULT',
                'isDefault' => true,
                'dns' => ['hostname' => 'app.cleverapps.io', 'aRecords' => ['1.2.3.4']],
            ],
        ]));

        $lbs = $this->resource($psr18)->list('app_1');

        self::assertCount(1, $lbs);
        self::assertSame('lb_default', $lbs[0]->id);
        self::assertTrue($lbs[0]->isDefault);
        self::assertSame(['hostname' => 'app.cleverapps.io', 'aRecords' => ['1.2.3.4']], $lbs[0]->dns);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/self/applications/app_1/load-balancers',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testDnsInfoHitsDefaultDns(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, ['hostname' => 'app.cleverapps.io']));

        $dns = $this->resource($psr18)->dnsInfo('app_1', 'orga_1');

        self::assertSame(['hostname' => 'app.cleverapps.io'], $dns);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/organisations/orga_1/applications/app_1/load-balancers/default/dns',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    private function resource(RecordingClient $psr18): LoadBalancersResource
    {
        return new LoadBalancersResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
