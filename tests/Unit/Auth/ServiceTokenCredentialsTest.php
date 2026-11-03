<?php

namespace CleverCloud\Sdk\Tests\Unit\Auth;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\ServiceTokenCredentials;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ServiceTokenCredentials::class)]
#[CoversClass(Credentials::class)]
final class ServiceTokenCredentialsTest extends TestCase
{
    public function testFactoryBuildsServiceTokenCredentials(): void
    {
        self::assertSame('biscuit_value', Credentials::serviceToken('biscuit_value')->token);
    }

    public function testV2RequestKeepsApiHostAndSendsBearer(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->client($response)->http->request('GET', ApiVersion::V2, '/organisations/orga_1/applications');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/applications', $response->getRequestUrl());
        self::assertSame('Authorization: Bearer biscuit_value', ResourceFactory::findHeader($response, 'Authorization'));
    }

    public function testV4RequestKeepsApiHostAndSendsBearer(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->client($response)->http->request('GET', ApiVersion::V4, '/networkgroups/organisations/orga_1/networkgroups');

        self::assertSame(
            'https://api.clever-cloud.com/v4/networkgroups/organisations/orga_1/networkgroups',
            $response->getRequestUrl(),
        );
        self::assertSame('Authorization: Bearer biscuit_value', ResourceFactory::findHeader($response, 'Authorization'));
    }

    public function testBridgeRequestIsLeftOnBridgeHost(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->client($response)->http->request('GET', ApiVersion::Bridge, '/api-tokens');

        self::assertSame('https://api-bridge.clever-cloud.com/api-tokens', $response->getRequestUrl());
        self::assertSame('Authorization: Bearer biscuit_value', ResourceFactory::findHeader($response, 'Authorization'));
    }

    private function client(MockResponse $response): Client
    {
        return new ClientBuilder()
            ->withCredentials(Credentials::serviceToken('biscuit_value'))
            ->withHttpClient(new MockHttpClient([$response]))
            ->build();
    }
}
