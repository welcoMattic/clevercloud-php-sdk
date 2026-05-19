<?php

namespace CleverCloud\Sdk;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\NonceGenerator;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Auth\RandomNonceGenerator;
use CleverCloud\Sdk\Exception\ConfigurationException;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Http\JsonCodec;
use CleverCloud\Sdk\Http\RetryPolicy;
use CleverCloud\Sdk\Http\UriBuilder;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\NativeClock;

final class ClientBuilder
{
    private ?Credentials $credentials = null;
    private ?Configuration $configuration = null;
    private ?ClientInterface $psr18 = null;
    private ?RequestFactoryInterface $requestFactory = null;
    private ?StreamFactoryInterface $streamFactory = null;
    private ?UriFactoryInterface $uriFactory = null;
    private ?ClockInterface $clock = null;
    private ?NonceGenerator $nonceGenerator = null;
    private ?RetryPolicy $retryPolicy = null;
    private ?LoggerInterface $logger = null;

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

    public function withHttpClient(ClientInterface $client): self
    {
        $clone = clone $this;
        $clone->psr18 = $client;

        return $clone;
    }

    public function withRequestFactory(RequestFactoryInterface $factory): self
    {
        $clone = clone $this;
        $clone->requestFactory = $factory;

        return $clone;
    }

    public function withStreamFactory(StreamFactoryInterface $factory): self
    {
        $clone = clone $this;
        $clone->streamFactory = $factory;

        return $clone;
    }

    public function withUriFactory(UriFactoryInterface $factory): self
    {
        $clone = clone $this;
        $clone->uriFactory = $factory;

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

    public function build(): Client
    {
        if (null === $this->credentials) {
            throw new ConfigurationException('Cannot build CleverCloud client without credentials. Call withCredentials() first.');
        }

        $configuration = $this->configuration ?? new Configuration();
        $psr18 = $this->psr18 ?? Psr18ClientDiscovery::find();
        $requestFactory = $this->requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $this->streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $uriFactory = $this->uriFactory ?? Psr17FactoryDiscovery::findUriFactory();

        $signer = new OAuth1Signer(
            $this->clock ?? new NativeClock(),
            $this->nonceGenerator ?? new RandomNonceGenerator(),
        );

        $http = new HttpClient(
            psr18: $psr18,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            uriBuilder: new UriBuilder($configuration, $uriFactory),
            signer: $signer,
            credentials: $this->credentials,
            configuration: $configuration,
            jsonCodec: new JsonCodec(),
            retryPolicy: $this->retryPolicy ?? new RetryPolicy(),
            logger: $this->logger,
        );

        return new Client($http);
    }
}
