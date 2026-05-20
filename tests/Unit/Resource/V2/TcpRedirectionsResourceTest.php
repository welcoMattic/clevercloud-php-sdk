<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\TcpRedirectionsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(TcpRedirectionsResource::class)]
final class TcpRedirectionsResourceTest extends TestCase
{
    public function testListReturnsTypedRedirections(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['port' => 5040, 'namespace' => 'default'],
            ['port' => 5041, 'namespace' => 'cleverapps'],
        ]);

        $redirs = $this->resource($response)->list('app_1', 'orga_1');

        self::assertCount(2, $redirs);
        self::assertSame(5040, $redirs[0]->port);
        self::assertSame('default', $redirs[0]->namespace);
        self::assertSame(5041, $redirs[1]->port);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/tcp-redirs',
            $response->getRequestUrl(),
        );
    }

    public function testNamespacesReturnsStringList(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['namespace' => 'default'],
            ['namespace' => 'cleverapps'],
        ]);

        $names = $this->resource($response)->namespaces('orga_1');

        self::assertSame(['default', 'cleverapps'], $names);
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/tcp-redirs/namespaces',
            $response->getRequestUrl(),
        );
    }

    public function testAddPostsJsonNamespace(): void
    {
        $response = ResourceFactory::jsonResponse(201, ['port' => 5042, 'namespace' => 'default']);

        $redir = $this->resource($response)->add('app_1', 'default', 'orga_1');

        self::assertSame(5042, $redir->port);
        self::assertSame('default', $redir->namespace);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('{"namespace":"default"}', $response->getRequestOptions()['body']);
    }

    public function testRemoveBuildsPortPathWithNamespaceQuery(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->remove('app_1', 5040, 'default', 'orga_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_1/tcp-redirs/5040?namespace=default',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): TcpRedirectionsResource
    {
        return new TcpRedirectionsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
