<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\DomainsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(DomainsResource::class)]
final class DomainsResourceTest extends TestCase
{
    public function testListReturnsVhosts(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['fqdn' => 'one.example.com'],
            ['fqdn' => 'two.example.com'],
        ]);

        $vhosts = $this->resource($response)->list('app_1');

        self::assertCount(2, $vhosts);
        self::assertSame('one.example.com', $vhosts[0]->fqdn);
        self::assertSame('two.example.com', $vhosts[1]->fqdn);
    }

    public function testAddPostsToVhostsByFqdn(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->add('app_1', 'demo.example.com', 'orga_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/vhosts/demo.example.com',
            $response->getRequestUrl(),
        );
    }

    public function testFavouriteReadsCurrentChoice(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['fqdn' => 'starred.example.com']);

        $vhost = $this->resource($response)->favourite('app_1');

        self::assertNotNull($vhost);
        self::assertSame('starred.example.com', $vhost->fqdn);
    }

    public function testFavouriteReturnsNullWhenEmpty(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        self::assertNull($this->resource($response)->favourite('app_1'));
    }

    public function testSetFavouritePutsJsonBody(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->setFavourite('app_1', 'starred.example.com');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/vhosts/favourite',
            $response->getRequestUrl(),
        );
        self::assertSame('{"fqdn":"starred.example.com"}', $response->getRequestOptions()['body']);
    }

    private function resource(MockResponse $response): DomainsResource
    {
        return new DomainsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
