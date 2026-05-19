<?php

namespace CleverCloud\Sdk\Http;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Configuration;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final readonly class UriBuilder
{
    public function __construct(
        private Configuration $configuration,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     */
    public function build(ApiVersion $version, string $path, array $query = []): UriInterface
    {
        $base = rtrim($this->configuration->baseUrlFor($version), '/');
        $path = '/'.ltrim($path, '/');

        $uri = $this->uriFactory->createUri($base.$path);
        if ([] === $query) {
            return $uri;
        }

        return $uri->withQuery(self::buildQuery($query));
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     */
    private static function buildQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            if (null === $value) {
                continue;
            }
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
