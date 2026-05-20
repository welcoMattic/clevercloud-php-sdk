<?php

namespace CleverCloud\Sdk;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\NonceGenerator;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Auth\RandomNonceGenerator;
use CleverCloud\Sdk\Exception\ConfigurationException;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Http\JsonCodec;
use CleverCloud\Sdk\Http\RetryPolicy;
use CleverCloud\Sdk\Http\UriBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpClient\HttpClient as SfHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface as SfHttpClientInterface;

final class ClientBuilder
{
    private ?Credentials $credentials = null;
    private ?Configuration $configuration = null;
    private ?SfHttpClientInterface $sfHttpClient = null;
    private ?ClockInterface $clock = null;
    private ?NonceGenerator $nonceGenerator = null;
    private ?RetryPolicy $retryPolicy = null;
    private ?LoggerInterface $logger = null;
    private ?AutoMapperInterface $mapper = null;

    public function withCredentials(Credentials $credentials): self
    {
        $clone = clone $this;
        $clone->credentials = $credentials;

        return $clone;
    }

    public function withConfiguration(Configuration $configuration): self
    {
        $clone = clone $this;
        $clone->configuration = $configuration;

        return $clone;
    }

    /**
     * Override the Symfony HttpClient used by the SDK. The provided client is
     * used both for regular request/response calls (via a {@see Psr18Client}
     * adapter) and for SSE streaming (wrapped in an `EventSourceHttpClient`).
     * Defaults to {@see SfHttpClient::create()} when omitted.
     */
    public function withHttpClient(SfHttpClientInterface $client): self
    {
        $clone = clone $this;
        $clone->sfHttpClient = $client;

        return $clone;
    }

    public function withClock(ClockInterface $clock): self
    {
        $clone = clone $this;
        $clone->clock = $clock;

        return $clone;
    }

    public function withNonceGenerator(NonceGenerator $nonceGenerator): self
    {
        $clone = clone $this;
        $clone->nonceGenerator = $nonceGenerator;

        return $clone;
    }

    public function withRetryPolicy(RetryPolicy $policy): self
    {
        $clone = clone $this;
        $clone->retryPolicy = $policy;

        return $clone;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $clone = clone $this;
        $clone->logger = $logger;

        return $clone;
    }

    public function withMapper(AutoMapperInterface $mapper): self
    {
        $clone = clone $this;
        $clone->mapper = $mapper;

        return $clone;
    }

    public function build(): Client
    {
        if (null === $this->credentials) {
            throw new ConfigurationException('Cannot build CleverCloud client without credentials. Call withCredentials() first.');
        }

        $configuration = $this->configuration ?? new Configuration();
        $sfHttpClient = $this->sfHttpClient ?? SfHttpClient::create();
        $psr17 = new Psr17Factory();
        $psr18 = new Psr18Client($sfHttpClient, $psr17, $psr17);

        $signer = new OAuth1Signer(
            $this->clock ?? new NativeClock(),
            $this->nonceGenerator ?? new RandomNonceGenerator(),
        );

        $http = new HttpClient(
            psr18: $psr18,
            requestFactory: $psr17,
            streamFactory: $psr17,
            uriBuilder: new UriBuilder($configuration, $psr17),
            signer: $signer,
            credentials: $this->credentials,
            configuration: $configuration,
            jsonCodec: new JsonCodec(),
            retryPolicy: $this->retryPolicy ?? new RetryPolicy(),
            logger: $this->logger,
            sfHttpClient: $sfHttpClient,
        );

        $mapper = $this->mapper ?? AutoMapper::create();

        return new Client($http, $mapper);
    }
}
