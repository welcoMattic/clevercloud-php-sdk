<?php

namespace CleverCloud\Sdk\Http;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\AuthException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Exception\RateLimitException;
use CleverCloud\Sdk\Exception\ServerException;
use CleverCloud\Sdk\Exception\TransportException;
use CleverCloud\Sdk\Exception\ValidationException;
use Closure;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The single HTTP entry point used by every resource client.
 *
 * Inlines four concerns — request building, OAuth signing, retry on 429 / 5xx,
 * and error-to-exception mapping — so there is no middleware chain to reason
 * about.
 *
 * @phpstan-type RequestOptions array{
 *     query?: array<string, scalar|list<scalar>|null>,
 *     headers?: array<string, string>,
 *     json?: mixed,
 *     form?: array<string, scalar|list<scalar>>,
 *     body?: string,
 * }
 */
final class HttpClient
{
    /** @var Closure(int): void */
    private Closure $sleeper;

    public function __construct(
        private readonly ClientInterface $psr18,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly OAuth1Signer $signer,
        private readonly Credentials $credentials,
        private readonly Configuration $configuration,
        private readonly JsonCodec $jsonCodec,
        private readonly RetryPolicy $retryPolicy = new RetryPolicy(),
        private readonly ?LoggerInterface $logger = null,
        ?Closure $sleeper = null,
    ) {
        $this->sleeper = $sleeper ?? static function (int $delayMs): void {
            if ($delayMs > 0) {
                usleep($delayMs * 1_000);
            }
        };
    }

    /**
     * Decodes the JSON response body and returns the resulting array.
     *
     * @param RequestOptions $options
     *
     * @return array<int|string, mixed>
     */
    public function request(string $method, ApiVersion $version, string $path, array $options = []): array
    {
        $response = $this->dispatch($this->buildRequest($method, $version, $path, $options));

        return $this->jsonCodec->decode((string) $response->getBody());
    }

    /**
     * Returns the raw response — bypasses JSON decoding so callers can stream SSE
     * (or download non-JSON content).
     *
     * @param RequestOptions $options
     */
    public function stream(string $method, ApiVersion $version, string $path, array $options = []): ResponseInterface
    {
        return $this->dispatch($this->buildRequest($method, $version, $path, $options));
    }

    /**
     * @param RequestOptions $options
     */
    private function buildRequest(string $method, ApiVersion $version, string $path, array $options): RequestInterface
    {
        $uri = $this->uriBuilder->build($version, $path, $options['query'] ?? []);
        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('User-Agent', $this->configuration->userAgent)
            ->withHeader('Accept', 'application/json');

        foreach ($options['headers'] ?? [] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (\array_key_exists('json', $options)) {
            $body = $this->streamFactory->createStream($this->jsonCodec->encode($options['json']));

            return $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body);
        }

        if (isset($options['form'])) {
            $body = $this->streamFactory->createStream(self::encodeForm($options['form']));

            return $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body);
        }

        if (isset($options['body'])) {
            return $request->withBody($this->streamFactory->createStream($options['body']));
        }

        return $request;
    }

    private function dispatch(RequestInterface $request): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            ++$attempt;

            $signed = $this->signer->sign($request, $this->credentials);

            try {
                $response = $this->psr18->sendRequest($signed);
            } catch (ClientExceptionInterface $e) {
                $this->logger?->warning('clevercloud-sdk: transport error', [
                    'attempt' => $attempt,
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'exception' => $e->getMessage(),
                ]);

                if ($attempt >= $this->retryPolicy->maxAttempts) {
                    throw new TransportException(\sprintf('Transport error after %d attempt(s): %s', $attempt, $e->getMessage()), 0, $e);
                }

                $this->wait($this->retryPolicy->delayFor($attempt));
                continue;
            }

            $status = $response->getStatusCode();

            $this->logger?->debug('clevercloud-sdk: response', [
                'attempt' => $attempt,
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status' => $status,
                'requestId' => self::extractRequestId($response),
            ]);

            if ($status >= 200 && $status < 300) {
                return $response;
            }

            if (429 === $status && $attempt < $this->retryPolicy->maxAttempts) {
                $delay = self::retryAfterMs($response) ?? $this->retryPolicy->delayFor($attempt);
                $this->logger?->warning('clevercloud-sdk: rate-limited, retrying', [
                    'attempt' => $attempt,
                    'delayMs' => $delay,
                ]);
                $this->wait($delay);
                continue;
            }

            if ($status >= 500 && $attempt < $this->retryPolicy->maxAttempts) {
                $delay = $this->retryPolicy->delayFor($attempt);
                $this->logger?->warning('clevercloud-sdk: server error, retrying', [
                    'attempt' => $attempt,
                    'status' => $status,
                    'delayMs' => $delay,
                ]);
                $this->wait($delay);
                continue;
            }

            $exception = $this->mapError($response);
            $this->logger?->error('clevercloud-sdk: terminal error', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status' => $status,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function wait(int $delayMs): void
    {
        ($this->sleeper)($delayMs);
    }

    private function mapError(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $body = $this->decodeBodySafely($response);
        $message = self::extractMessage($body, $response);
        $errorCode = self::extractErrorCode($body);
        $requestId = self::extractRequestId($response);

        return match (true) {
            401 === $status, 403 === $status => new AuthException($message, $status, $errorCode, $requestId, $body),
            404 === $status => new NotFoundException($message, $status, $errorCode, $requestId, $body),
            400 === $status, 422 === $status => new ValidationException(
                $message,
                self::extractValidationErrors($body),
                $status,
                $errorCode,
                $requestId,
                $body,
            ),
            429 === $status => new RateLimitException(
                $message,
                self::retryAfterSeconds($response),
                $status,
                $errorCode,
                $requestId,
                $body,
            ),
            $status >= 500 => new ServerException($message, $status, $errorCode, $requestId, $body),
            default => new ApiException($message, $status, $errorCode, $requestId, $body),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBodySafely(ResponseInterface $response): array
    {
        $raw = (string) $response->getBody();
        if ('' === $raw) {
            return [];
        }

        try {
            $decoded = $this->jsonCodec->decode($raw);
        } catch (Throwable) {
            return ['_raw' => $raw];
        }

        if (!array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            return $decoded;
        }

        return ['_raw' => $decoded];
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function extractMessage(array $body, ResponseInterface $response): string
    {
        foreach (['message', 'error', 'error_description', 'detail'] as $key) {
            if (isset($body[$key]) && \is_string($body[$key]) && '' !== $body[$key]) {
                return $body[$key];
            }
        }

        $reason = $response->getReasonPhrase();

        return '' !== $reason ? $reason : \sprintf('HTTP %d', $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function extractErrorCode(array $body): ?string
    {
        foreach (['code', 'error_code', 'type'] as $key) {
            if (isset($body[$key]) && \is_string($body[$key]) && '' !== $body[$key]) {
                return $body[$key];
            }
        }

        return null;
    }

    private static function extractRequestId(ResponseInterface $response): ?string
    {
        foreach (['X-Request-Id', 'Sozu-Id', 'X-Sozu-Id'] as $header) {
            $value = $response->getHeaderLine($header);
            if ('' !== $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, list<string>>
     */
    private static function extractValidationErrors(array $body): array
    {
        $candidate = $body['errors'] ?? $body['violations'] ?? null;
        if (!\is_array($candidate)) {
            return [];
        }

        $errors = [];
        foreach ($candidate as $field => $messages) {
            if (!\is_string($field)) {
                continue;
            }
            if (\is_string($messages)) {
                $errors[$field] = [$messages];
                continue;
            }
            if (!\is_array($messages)) {
                continue;
            }
            $errors[$field] = array_values(array_filter(
                array_map(static fn (mixed $m): string => \is_string($m) ? $m : '', $messages),
                static fn (string $m): bool => '' !== $m,
            ));
        }

        return $errors;
    }

    private static function retryAfterSeconds(ResponseInterface $response): ?int
    {
        $value = $response->getHeaderLine('Retry-After');
        if ('' === $value) {
            return null;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return null;
        }

        return max(0, $timestamp - time());
    }

    private static function retryAfterMs(ResponseInterface $response): ?int
    {
        $seconds = self::retryAfterSeconds($response);

        return null === $seconds ? null : $seconds * 1_000;
    }

    /**
     * @param array<string, scalar|list<scalar>> $form
     */
    private static function encodeForm(array $form): string
    {
        $pairs = [];
        foreach ($form as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $entry) {
                    $pairs[] = rawurlencode($key).'='.rawurlencode((string) $entry);
                }
                continue;
            }
            $pairs[] = rawurlencode($key).'='.rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }
}
