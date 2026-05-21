<?php

namespace CleverCloud\Sdk\Tests\Unit;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\ConfigurationException;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ClientBuilder::class)]
#[CoversClass(Client::class)]
final class ClientBuilderTest extends TestCase
{
    public function testRejectsBuildWithoutCredentials(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('credentials');

        new ClientBuilder()->build();
    }

    public function testBuildsClientWithDiscoveredDefaults(): void
    {
        $this->expectNotToPerformAssertions();

        new ClientBuilder()
            ->withCredentials(Credentials::oauth1('ck', 'cs', 'tk', 'ts'))
            ->build();
    }

    public function testMemoizesResourceClients(): void
    {
        $client = new ClientBuilder()
            ->withCredentials(Credentials::oauth1('ck', 'cs', 'tk', 'ts'))
            ->build();

        self::assertSame($client->self, $client->self);
    }

    public function testCallsSelfEndpointWithInjectedHttpClient(): void
    {
        $mock = new MockHttpClient([
            new MockResponse(json_encode(['id' => 'me_1', 'email' => 'me@example.com'], JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type: application/json'],
            ]),
        ]);

        $client = new ClientBuilder()
            ->withCredentials(Credentials::oauth1('ck', 'cs', 'tk', 'ts'))
            ->withHttpClient($mock)
            ->build();

        $self = $client->self->get();

        self::assertSame('me_1', $self->id);
        self::assertSame('me@example.com', $self->email);
    }

    public function testBuilderIsImmutable(): void
    {
        $builder = new ClientBuilder();
        $withCreds = $builder->withCredentials(Credentials::oauth1('ck', 'cs'));

        self::assertNotSame($builder, $withCreds);
        $this->expectException(ConfigurationException::class);
        $builder->build();
    }
}
