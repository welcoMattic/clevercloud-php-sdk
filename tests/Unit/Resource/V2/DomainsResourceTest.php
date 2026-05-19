<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\DomainsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DomainsResource::class)]
final class DomainsResourceTest extends TestCase
{
    public function testListReturnsVhosts(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            ['fqdn' => 'one.example.com'],
            ['fqdn' => 'two.example.com'],
        ]));

        $vhosts = $this->resource($psr18)->list('app_1');

        self::assertCount(2, $vhosts);
        self::assertSame('one.example.com', $vhosts[0]->fqdn);
        self::assertSame('two.example.com', $vhosts[1]->fqdn);
    }

    public function testAddPostsToVhostsByFqdn(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->add('app_1', 'demo.example.com', 'orga_1');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/vhosts/demo.example.com',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testFavouriteReadsCurrentChoice(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, ['fqdn' => 'starred.example.com']));

        $vhost = $this->resource($psr18)->favourite('app_1');

        self::assertNotNull($vhost);
        self::assertSame('starred.example.com', $vhost->fqdn);
    }

    public function testFavouriteReturnsNullWhenEmpty(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        self::assertNull($this->resource($psr18)->favourite('app_1'));
    }

    public function testSetFavouritePutsJsonBody(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->setFavourite('app_1', 'starred.example.com');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('PUT', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/applications/app_1/vhosts/favourite',
            (string) $psr18->lastRequest->getUri(),
        );
        self::assertSame('{"fqdn":"starred.example.com"}', (string) $psr18->lastRequest->getBody());
    }

    private function resource(RecordingClient $psr18): DomainsResource
    {
        return new DomainsResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
