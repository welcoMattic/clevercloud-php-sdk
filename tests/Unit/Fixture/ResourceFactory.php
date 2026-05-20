<?php

namespace CleverCloud\Sdk\Tests\Unit\Fixture;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Http\JsonCodec;
use CleverCloud\Sdk\Http\RetryPolicy;
use CleverCloud\Sdk\Http\UriBuilder;
use CleverCloud\Sdk\Tests\Unit\Auth\StaticNonceGenerator;

use const JSON_THROW_ON_ERROR;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Test-only helpers for wiring up a resource client against Symfony's
 * {@see MockHttpClient}. Inspect requests post-call via
 * `$response->getRequestMethod() / getRequestUrl() / getRequestOptions()`.
 */
final class ResourceFactory
{
    public static function psr17(): Psr17Factory
    {
        return new Psr17Factory();
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    public static function jsonResponse(int $status, array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    }

    public static function emptyResponse(int $status): MockResponse
    {
        return new MockResponse('', ['http_code' => $status]);
    }

    public static function http(MockHttpClient $mock, ?RetryPolicy $policy = null): HttpClient
    {
        $factory = self::psr17();
        $configuration = new Configuration();
        $psr18 = new Psr18Client($mock, $factory, $factory);

        return new HttpClient(
            psr18: $psr18,
            requestFactory: $factory,
            streamFactory: $factory,
            uriBuilder: new UriBuilder($configuration, $factory),
            signer: new OAuth1Signer(new MockClock('@1700000000'), new StaticNonceGenerator('test-nonce')),
            credentials: new Credentials('ck', 'cs', 'tk', 'ts'),
            configuration: $configuration,
            jsonCodec: new JsonCodec(),
            retryPolicy: $policy ?? new RetryPolicy(maxAttempts: 1, jitterMs: 0),
            sfHttpClient: $mock,
        );
    }

    public static function mapper(): AutoMapperInterface
    {
        return AutoMapper::create();
    }

    /**
     * Symfony's `MockResponse::getRequestOptions()` returns `array<string, mixed>`;
     * normalising the `headers` slot keeps assertions readable and PHPStan happy.
     *
     * @return list<string>
     */
    public static function headers(MockResponse $response): array
    {
        $options = $response->getRequestOptions();
        $headers = $options['headers'] ?? [];
        if (!\is_array($headers)) {
            return [];
        }

        $lines = [];
        foreach ($headers as $line) {
            if (\is_string($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    public static function findHeader(MockResponse $response, string $name): ?string
    {
        $prefix = $name.':';
        foreach (self::headers($response) as $line) {
            if (str_starts_with($line, $prefix)) {
                return $line;
            }
        }

        return null;
    }
}
