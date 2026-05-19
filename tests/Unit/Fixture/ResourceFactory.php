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
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Clock\MockClock;

/**
 * Test-only helpers for wiring up a resource client against a {@see RecordingClient}.
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
    public static function jsonResponse(int $status, array $payload): ResponseInterface
    {
        $factory = self::psr17();

        return $factory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    public static function emptyResponse(int $status): ResponseInterface
    {
        return self::psr17()->createResponse($status);
    }

    public static function http(RecordingClient $psr18, ?RetryPolicy $policy = null): HttpClient
    {
        $factory = self::psr17();
        $configuration = new Configuration();

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
        );
    }

    public static function mapper(): AutoMapperInterface
    {
        return AutoMapper::create();
    }
}
