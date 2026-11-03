<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\PulsarResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(PulsarResource::class)]
final class PulsarResourceTest extends TestCase
{
    private const string BASE = 'https://api.clever-cloud.com/v4/addon-providers/addon-pulsar';

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function topicKinds(): iterable
    {
        yield 'persistent' => [true, 'topics'];
        yield 'non-persistent' => [false, 'non-persistent-topics'];
    }

    #[DataProvider('topicKinds')]
    public function testTopicsReturnsTypedList(bool $persistent, string $segment): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['name' => 'persistent://tenant/ns/orders'],
        ]);

        $topics = $this->resource($response)->topics('pulsar_1', $persistent);

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/addons/pulsar_1/'.$segment, $response->getRequestUrl());
        self::assertCount(1, $topics);
        self::assertSame('persistent://tenant/ns/orders', $topics[0]->name);
    }

    #[DataProvider('topicKinds')]
    public function testCreateTopicPostsWithoutBody(bool $persistent, string $segment): void
    {
        $response = ResourceFactory::emptyResponse(201);

        $this->resource($response)->createTopic('pulsar_1', 'orders', $persistent);

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/addons/pulsar_1/'.$segment.'/orders', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    #[DataProvider('topicKinds')]
    public function testDeleteTopicHitsDelete(bool $persistent, string $segment): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->deleteTopic('pulsar_1', 'orders', $persistent);

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::BASE.'/addons/pulsar_1/'.$segment.'/orders', $response->getRequestUrl());
    }

    #[DataProvider('topicKinds')]
    public function testCreateTopicTokenReturnsPlainTextToken(bool $persistent, string $segment): void
    {
        $response = new MockResponse('En0KEwoEdGVzdBgD', [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'text/plain'],
        ]);

        $token = $this->resource($response)->createTopicToken('pulsar_1', 'orders', $persistent);

        self::assertSame('En0KEwoEdGVzdBgD', $token);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/addons/pulsar_1/'.$segment.'/orders/token', $response->getRequestUrl());
        self::assertSame('Accept: text/plain', ResourceFactory::findHeader($response, 'Accept'));
    }

    #[DataProvider('topicKinds')]
    public function testUnloadTopicPutsToUnload(bool $persistent, string $segment): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->unloadTopic('pulsar_1', 'orders', $persistent);

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame(self::BASE.'/addons/pulsar_1/'.$segment.'/orders/unload', $response->getRequestUrl());
    }

    public function testTopicNameIsUrlEncoded(): void
    {
        $response = ResourceFactory::emptyResponse(201);

        $this->resource($response)->createTopic('pulsar_1', 'a b/c');

        self::assertSame(self::BASE.'/addons/pulsar_1/topics/a%20b%2Fc', $response->getRequestUrl());
    }

    public function testClusterMapsSnakeCaseFields(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'cluster_1',
            'url' => 'pulsar.example',
            'pulsar_port' => 6650,
            'pulsar_tls_port' => 6651,
            'web_port' => null,
            'web_tls_port' => 443,
            'admin_host' => null,
            'version' => '4.0.0',
            'available' => true,
            'zone' => 'par',
            'support_cold_storage' => true,
            'supported_plans' => ['BETA'],
        ]);

        $cluster = $this->resource($response)->cluster('cluster_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/clusters/cluster_1', $response->getRequestUrl());
        self::assertSame('cluster_1', $cluster->id);
        self::assertSame('pulsar.example', $cluster->url);
        self::assertSame(6650, $cluster->pulsarPort);
        self::assertSame(6651, $cluster->pulsarTlsPort);
        self::assertNull($cluster->webPort);
        self::assertSame(443, $cluster->webTlsPort);
        self::assertSame('4.0.0', $cluster->version);
        self::assertTrue($cluster->available);
        self::assertSame('par', $cluster->zone);
        self::assertTrue($cluster->supportColdStorage);
        self::assertSame(['BETA'], $cluster->supportedPlans);
    }

    private function resource(MockResponse $response): PulsarResource
    {
        return new PulsarResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
