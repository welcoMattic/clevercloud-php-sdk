<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\LoadBalancersResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

#[CoversClass(LoadBalancersResource::class)]
final class LoadBalancersResourceTest extends TestCase
{
    public function testListReadsTheDefaultLoadBalancersOfTheOrganisation(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'loadBalancer_1',
                'name' => 'par',
                'zoneId' => 'aad32a21-24f8-40b3-a750-baab218d927b',
                'dns' => ['cname' => 'domain.par.clever-cloud.com.', 'a' => ['91.208.207.214', '91.208.207.215']],
            ],
        ]);

        $lbs = $this->resource(new MockHttpClient([$response]))->list('app_1', 'orga_1');

        self::assertCount(1, $lbs);
        self::assertSame('loadBalancer_1', $lbs[0]->id);
        self::assertSame('par', $lbs[0]->name);
        self::assertSame('aad32a21-24f8-40b3-a750-baab218d927b', $lbs[0]->zoneId);
        self::assertSame(['cname' => 'domain.par.clever-cloud.com.', 'a' => ['91.208.207.214', '91.208.207.215']], $lbs[0]->dns);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/organisations/orga_1/applications/app_1/load-balancers/default',
            $response->getRequestUrl(),
        );
    }

    public function testListResolvesThePersonalOrganisationAndPassesTheKind(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, []);

        $lbs = $this->resource(new MockHttpClient([$self, $response]))->list('app_1', kind: 'private');

        self::assertSame([], $lbs);
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame(
            'https://api.clever-cloud.com/v4/load-balancers/organisations/user_1/applications/app_1/load-balancers/private',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockHttpClient $mock): LoadBalancersResource
    {
        return new LoadBalancersResource(ResourceFactory::http($mock), ResourceFactory::mapper());
    }
}
