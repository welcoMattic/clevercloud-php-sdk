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
use CleverCloud\Sdk\Tests\Unit\Auth\FrozenClock;
use CleverCloud\Sdk\Tests\Unit\Auth\StaticNonceGenerator;

use const JSON_THROW_ON_ERROR;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

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
        $psr18 = new QueueClient([
            $this->jsonResponse(200, ['id' => 'app_1', 'name' => 'hello']),
        ]);
        $client = $this->buildClient($psr18);

        $payload = $client->request('GET', ApiVersion::V2, '/self');

        self::assertSame(['id' => 'app_1', 'name' => 'hello'], $payload);
        self::assertSame(1, $psr18->callCount);
        self::assertSame([], $this->sleeps);
    }

    public function testReturnsRawResponseForStream(): void
    {
        $psr18 = new QueueClient([
            $this->factory->createResponse(200)->withBody($this->factory->createStream('streamed')),
        ]);
        $client = $this->buildClient($psr18);

        $response = $client->stream('GET', ApiVersion::V4, '/logs');

        self::assertSame('streamed', (string) $response->getBody());
    }

    public function testRetriesOn429HonouringRetryAfter(): void
    {
        $tooMany = $this->jsonResponse(429, ['message' => 'slow down'])
            ->withHeader('Retry-After', '2');

        $psr18 = new QueueClient([
            $tooMany,
            $this->jsonResponse(200, ['ok' => true]),
        ]);
        $client = $this->buildClient($psr18, new RetryPolicy(maxAttempts: 3, jitterMs: 0));

        $payload = $client->request('GET', ApiVersion::V2, '/self');

        self::assertSame(['ok' => true], $payload);
        self::assertSame(2, $psr18->callCount);
        self::assertSame([2_000], $this->sleeps);
    }

    public function testRetriesOn5xxWithExponentialBackoff(): void
    {
        $psr18 = new QueueClient([
            $this->jsonResponse(500, ['error' => 'oops']),
            $this->jsonResponse(503, ['error' => 'unavailable']),
            $this->jsonResponse(200, ['ok' => true]),
        ]);
        $policy = new RetryPolicy(maxAttempts: 3, baseDelayMs: 100, multiplier: 2.0, jitterMs: 0);
        $client = $this->buildClient($psr18, $policy);

        $payload = $client->request('GET', ApiVersion::V2, '/applications');

        self::assertSame(['ok' => true], $payload);
        self::assertSame(3, $psr18->callCount);
        self::assertSame([100, 200], $this->sleeps);
    }

    public function testRaisesServerExceptionWhenRetriesExhausted(): void
    {
        $psr18 = new QueueClient([
            $this->jsonResponse(500, ['error' => 'boom']),
            $this->jsonResponse(500, ['error' => 'boom']),
            $this->jsonResponse(500, ['error' => 'boom']),
        ]);
        $client = $this->buildClient($psr18, new RetryPolicy(maxAttempts: 3, baseDelayMs: 10, jitterMs: 0));

        try {
            $client->request('GET', ApiVersion::V2, '/applications');
            self::fail('Expected ServerException');
        } catch (ServerException $e) {
            self::assertSame(500, $e->statusCode);
            self::assertSame('boom', $e->getMessage());
            self::assertSame(3, $psr18->callCount);
            self::assertCount(2, $this->sleeps);
        }
    }

    public function testRaisesAuthExceptionOn401WithoutRetry(): void
    {
        $psr18 = new QueueClient([
            $this->jsonResponse(401, ['message' => 'bad token'])->withHeader('X-Request-Id', 'req-123'),
        ]);
        $client = $this->buildClient($psr18, new RetryPolicy(maxAttempts: 3));

        try {
            $client->request('GET', ApiVersion::V2, '/self');
            self::fail('Expected AuthException');
        } catch (AuthException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('bad token', $e->getMessage());
            self::assertSame('req-123', $e->requestId);
            self::assertSame(1, $psr18->callCount);
            self::assertSame([], $this->sleeps);
        }
    }

    public function testRaisesNotFoundExceptionOn404(): void
    {
        $psr18 = new QueueClient([
            $this->jsonResponse(404, ['message' => 'missing']),
        ]);
        $client = $this->buildClient($psr18);

        $this->expectException(NotFoundException::class);
        $client->request('GET', ApiVersion::V2, '/applications/zzz');
    }

    public function testRaisesValidationExceptionOn422WithFieldErrors(): void
    {
        $psr18 = new QueueClient([
            $this->jsonResponse(422, [
                'message' => 'invalid input',
                'errors' => [
                    'name' => ['must not be blank', 'too short'],
                    'zone' => 'unknown zone',
                ],
            ]),
        ]);
        $client = $this->buildClient($psr18);

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
        $psr18 = new QueueClient([
            $this->jsonResponse(418, ['message' => "I'm a teapot"]),
        ]);
        $client = $this->buildClient($psr18);

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
        $psr18 = new QueueClient([
            new TransportFailure('connection reset'),
            new TransportFailure('connection reset'),
        ]);
        $client = $this->buildClient($psr18, new RetryPolicy(maxAttempts: 2, baseDelayMs: 10, jitterMs: 0));

        $this->expectException(TransportException::class);
        $client->request('GET', ApiVersion::V2, '/self');
    }

    public function testSignsRequestBeforeSending(): void
    {
        $psr18 = new QueueClient([$this->jsonResponse(200, [])]);
        $client = $this->buildClient($psr18);

        $client->request('GET', ApiVersion::V2, '/self');

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        self::assertStringStartsWith('OAuth ', $sent->getHeaderLine('Authorization'));
        self::assertStringContainsString('oauth_signature_method="HMAC-SHA512"', $sent->getHeaderLine('Authorization'));
        self::assertSame('application/json', $sent->getHeaderLine('Accept'));
        self::assertSame(Configuration::DEFAULT_USER_AGENT, $sent->getHeaderLine('User-Agent'));
    }

    public function testBuildsFormBodyForFormOption(): void
    {
        $psr18 = new QueueClient([$this->jsonResponse(200, [])]);
        $client = $this->buildClient($psr18);

        $client->request('POST', ApiVersion::V2, '/self/applications', ['form' => ['name' => 'app', 'zone' => 'par']]);

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        self::assertSame('application/x-www-form-urlencoded', $sent->getHeaderLine('Content-Type'));
        self::assertSame('name=app&zone=par', (string) $sent->getBody());
    }

    public function testBuildsJsonBodyForJsonOption(): void
    {
        $psr18 = new QueueClient([$this->jsonResponse(200, [])]);
        $client = $this->buildClient($psr18);

        $client->request('POST', ApiVersion::V2, '/self/applications', ['json' => ['name' => 'app']]);

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        self::assertSame('application/json', $sent->getHeaderLine('Content-Type'));
        self::assertSame('{"name":"app"}', (string) $sent->getBody());
    }

    public function testAppendsQueryParameters(): void
    {
        $psr18 = new QueueClient([$this->jsonResponse(200, [])]);
        $client = $this->buildClient($psr18);

        $client->request('GET', ApiVersion::V2, '/applications', ['query' => ['limit' => 10, 'fields' => ['id', 'name']]]);

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        $uri = (string) $sent->getUri();
        self::assertSame('https://api.clever-cloud.com/v2/applications?limit=10&fields=id&fields=name', $uri);
    }

    public function testRoutesV4PathsToV4BaseUrl(): void
    {
        $psr18 = new QueueClient([$this->jsonResponse(200, [])]);
        $client = $this->buildClient($psr18);

        $client->request('GET', ApiVersion::V4, '/billing/balance');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v4/billing/balance', (string) $psr18->lastRequest->getUri());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        return $this->factory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    private function buildClient(QueueClient $psr18, ?RetryPolicy $policy = null): HttpClient
    {
        $configuration = new Configuration();
        $signer = new OAuth1Signer(
            new FrozenClock(1_700_000_000),
            new StaticNonceGenerator('test-nonce'),
        );

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

final class QueueClient implements ClientInterface
{
    public int $callCount = 0;
    public ?RequestInterface $lastRequest = null;

    /**
     * @param list<ResponseInterface|Throwable> $queue
     */
    public function __construct(private array $queue)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        ++$this->callCount;
        $this->lastRequest = $request;

        if ([] === $this->queue) {
            throw new RuntimeException('QueueClient: no more queued responses');
        }

        $next = array_shift($this->queue);
        if ($next instanceof Throwable) {
            /** @var ClientExceptionInterface $next */
            throw $next;
        }

        return $next;
    }
}

final class TransportFailure extends RuntimeException implements ClientExceptionInterface
{
}
