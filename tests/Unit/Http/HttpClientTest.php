<?php

namespace CleverCloud\Sdk\Tests\Unit\Http;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\AuthException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Exception\ServerException;
use CleverCloud\Sdk\Exception\TransportException;
use CleverCloud\Sdk\Exception\ValidationException;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Http\JsonCodec;
use CleverCloud\Sdk\Http\RetryPolicy;
use CleverCloud\Sdk\Http\UriBuilder;
use CleverCloud\Sdk\Tests\Unit\Auth\StaticNonceGenerator;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;

use const JSON_THROW_ON_ERROR;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(HttpClient::class)]
final class HttpClientTest extends TestCase
{
    private Psr17Factory $factory;
    /** @var list<int> */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->sleeps = [];
    }

    public function testDecodesJsonOnSuccess(): void
    {
        $client = $this->buildClient([$this->jsonResponse(200, ['id' => 'app_1', 'name' => 'hello'])]);

        $payload = $client->request('GET', ApiVersion::V2, '/self');

        self::assertSame(['id' => 'app_1', 'name' => 'hello'], $payload);
        self::assertSame([], $this->sleeps);
    }

    public function testReturnsRawResponseForStream(): void
    {
        $client = $this->buildClient([new MockResponse('streamed', ['http_code' => 200])]);

        $response = $client->stream('GET', ApiVersion::V4, '/logs');

        self::assertSame('streamed', (string) $response->getBody());
    }

    public function testRetriesOn429HonouringRetryAfter(): void
    {
        $responses = [
            new MockResponse(json_encode(['message' => 'slow down'], JSON_THROW_ON_ERROR), [
                'http_code' => 429,
                'response_headers' => ['retry-after' => '2', 'content-type' => 'application/json'],
            ]),
            $this->jsonResponse(200, ['ok' => true]),
        ];
        $client = $this->buildClient($responses, new RetryPolicy(maxAttempts: 3, jitterMs: 0));

        $payload = $client->request('GET', ApiVersion::V2, '/self');

        self::assertSame(['ok' => true], $payload);
        self::assertSame([2_000], $this->sleeps);
    }

    public function testRetriesOn5xxWithExponentialBackoff(): void
    {
        $responses = [
            $this->jsonResponse(500, ['error' => 'oops']),
            $this->jsonResponse(503, ['error' => 'unavailable']),
            $this->jsonResponse(200, ['ok' => true]),
        ];
        $client = $this->buildClient($responses, new RetryPolicy(maxAttempts: 3, baseDelayMs: 100, multiplier: 2.0, jitterMs: 0));

        $payload = $client->request('GET', ApiVersion::V2, '/applications');

        self::assertSame(['ok' => true], $payload);
        self::assertSame([100, 200], $this->sleeps);
    }

    public function testRaisesServerExceptionWhenRetriesExhausted(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(500, ['error' => 'boom']),
            $this->jsonResponse(500, ['error' => 'boom']),
            $this->jsonResponse(500, ['error' => 'boom']),
        ], new RetryPolicy(maxAttempts: 3, baseDelayMs: 10, jitterMs: 0));

        try {
            $client->request('GET', ApiVersion::V2, '/applications');
            self::fail('Expected ServerException');
        } catch (ServerException $e) {
            self::assertSame(500, $e->statusCode);
            self::assertSame('boom', $e->getMessage());
            self::assertCount(2, $this->sleeps);
        }
    }

    public function testRaisesAuthExceptionOn401WithoutRetry(): void
    {
        $client = $this->buildClient([
            new MockResponse(json_encode(['message' => 'bad token'], JSON_THROW_ON_ERROR), [
                'http_code' => 401,
                'response_headers' => ['x-request-id' => 'req-123', 'content-type' => 'application/json'],
            ]),
        ], new RetryPolicy(maxAttempts: 3));

        try {
            $client->request('GET', ApiVersion::V2, '/self');
            self::fail('Expected AuthException');
        } catch (AuthException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('bad token', $e->getMessage());
            self::assertSame('req-123', $e->requestId);
            self::assertSame([], $this->sleeps);
        }
    }

    public function testRaisesNotFoundExceptionOn404(): void
    {
        $client = $this->buildClient([$this->jsonResponse(404, ['message' => 'missing'])]);

        $this->expectException(NotFoundException::class);
        $client->request('GET', ApiVersion::V2, '/applications/zzz');
    }

    public function testRaisesValidationExceptionOn422WithFieldErrors(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(422, [
                'message' => 'invalid input',
                'errors' => [
                    'name' => ['must not be blank', 'too short'],
                    'zone' => 'unknown zone',
                ],
            ]),
        ]);

        try {
            $client->request('POST', ApiVersion::V2, '/applications', ['json' => []]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(422, $e->statusCode);
            self::assertSame('invalid input', $e->getMessage());
            self::assertSame([
                'name' => ['must not be blank', 'too short'],
                'zone' => ['unknown zone'],
            ], $e->errors);
        }
    }

    public function testRaisesGenericApiExceptionForUnclassified4xx(): void
    {
        $client = $this->buildClient([$this->jsonResponse(418, ['message' => "I'm a teapot"])]);

        try {
            $client->request('GET', ApiVersion::V2, '/teapot');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(418, $e->statusCode);
            self::assertNotInstanceOf(AuthException::class, $e);
        }
    }

    public function testWrapsTransportExceptionAfterRetries(): void
    {
        $client = $this->buildClient([
            new MockResponse('', ['error' => 'connection reset']),
            new MockResponse('', ['error' => 'connection reset']),
        ], new RetryPolicy(maxAttempts: 2, baseDelayMs: 10, jitterMs: 0));

        $this->expectException(TransportException::class);
        $client->request('GET', ApiVersion::V2, '/self');
    }

    public function testSignsRequestBeforeSending(): void
    {
        $response = $this->jsonResponse(200, []);
        $this->buildClient([$response])->request('GET', ApiVersion::V2, '/self');

        $auth = ResourceFactory::findHeader($response, 'Authorization');
        self::assertNotNull($auth);
        self::assertStringStartsWith('Authorization: OAuth ', $auth);
        self::assertStringContainsString('oauth_signature_method="HMAC-SHA512"', $auth);

        $headers = ResourceFactory::headers($response);
        self::assertContains('Accept: application/json', $headers);
        self::assertContains('User-Agent: '.Configuration::DEFAULT_USER_AGENT, $headers);
    }

    public function testBuildsFormBodyForFormOption(): void
    {
        $response = $this->jsonResponse(200, []);
        $this->buildClient([$response])->request(
            'POST',
            ApiVersion::V2,
            '/self/applications',
            ['form' => ['name' => 'app', 'zone' => 'par']],
        );

        self::assertContains('Content-Type: application/x-www-form-urlencoded', ResourceFactory::headers($response));
        self::assertSame('name=app&zone=par', $response->getRequestOptions()['body']);
    }

    public function testBuildsJsonBodyForJsonOption(): void
    {
        $response = $this->jsonResponse(200, []);
        $this->buildClient([$response])->request(
            'POST',
            ApiVersion::V2,
            '/self/applications',
            ['json' => ['name' => 'app']],
        );

        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
        self::assertSame('{"name":"app"}', $response->getRequestOptions()['body']);
    }

    public function testAppendsQueryParameters(): void
    {
        $response = $this->jsonResponse(200, []);
        $this->buildClient([$response])->request(
            'GET',
            ApiVersion::V2,
            '/applications',
            ['query' => ['limit' => 10, 'fields' => ['id', 'name']]],
        );

        self::assertSame(
            'https://api.clever-cloud.com/v2/applications?limit=10&fields=id&fields=name',
            $response->getRequestUrl(),
        );
    }

    public function testRoutesV4PathsToV4BaseUrl(): void
    {
        $response = $this->jsonResponse(200, []);
        $this->buildClient([$response])->request('GET', ApiVersion::V4, '/billing/balance');

        self::assertSame('https://api.clever-cloud.com/v4/billing/balance', $response->getRequestUrl());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function buildClient(array $responses, ?RetryPolicy $policy = null): HttpClient
    {
        $configuration = new Configuration();
        $signer = new OAuth1Signer(
            new MockClock('@1700000000'),
            new StaticNonceGenerator('test-nonce'),
        );

        $mock = new MockHttpClient($responses);
        $psr18 = new Psr18Client($mock, $this->factory, $this->factory);

        return new HttpClient(
            psr18: $psr18,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
            uriBuilder: new UriBuilder($configuration, $this->factory),
            signer: $signer,
            credentials: new Credentials('ck', 'cs', 'tk', 'ts'),
            configuration: $configuration,
            jsonCodec: new JsonCodec(),
            retryPolicy: $policy ?? new RetryPolicy(maxAttempts: 1, jitterMs: 0),
            logger: null,
            sleeper: function (int $ms): void {
                $this->sleeps[] = $ms;
            },
        );
    }
}
