<?php

namespace CleverCloud\Sdk\Resource;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Exception\JsonException;
use CleverCloud\Sdk\Http\HttpClient;
use Psr\Http\Message\ResponseInterface;

abstract readonly class AbstractResource
{
    public function __construct(
        protected HttpClient $http,
        protected AutoMapperInterface $mapper,
    ) {
    }

    abstract protected function version(): ApiVersion;

    /**
     * @template T of object
     *
     * @param class-string<T>          $class
     * @param array<int|string, mixed> $payload
     *
     * @return T
     */
    protected function mapTo(string $class, array $payload): object
    {
        /** @var T|null $mapped */
        $mapped = $this->mapper->map($payload, $class);
        if (null === $mapped) {
            throw new JsonException(\sprintf('AutoMapper returned null mapping payload into %s', $class));
        }

        return $mapped;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>                $class
     * @param iterable<array<string, mixed>> $payloads
     *
     * @return list<T>
     */
    protected function mapCollection(string $class, iterable $payloads): array
    {
        /** @var list<T> $items */
        $items = array_values($this->mapper->mapCollection($payloads, $class));

        return $items;
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     *
     * @return array<int|string, mixed>
     */
    protected function httpGet(string $path, array $options = []): array
    {
        return $this->http->request('GET', $this->version(), $path, $options);
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     *
     * @return array<int|string, mixed>
     */
    protected function httpPost(string $path, array $options = []): array
    {
        return $this->http->request('POST', $this->version(), $path, $options);
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     *
     * @return array<int|string, mixed>
     */
    protected function httpPut(string $path, array $options = []): array
    {
        return $this->http->request('PUT', $this->version(), $path, $options);
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     *
     * @return array<int|string, mixed>
     */
    protected function httpPatch(string $path, array $options = []): array
    {
        return $this->http->request('PATCH', $this->version(), $path, $options);
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     *
     * @return array<int|string, mixed>
     */
    protected function httpDelete(string $path, array $options = []): array
    {
        return $this->http->request('DELETE', $this->version(), $path, $options);
    }

    /**
     * @param array{
     *     query?: array<string, scalar|list<scalar>|null>,
     *     headers?: array<string, string>,
     *     json?: mixed,
     *     form?: array<string, scalar|list<scalar>>,
     *     body?: string,
     * } $options
     */
    protected function httpStream(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->http->stream($method, $this->version(), $path, $options);
    }
}
