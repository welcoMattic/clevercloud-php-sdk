<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

/**
 * One HTTP call issued by the SDK: method, normalised path (parameters as
 * `{}`, host prefix when not the main API host) and the resource method that
 * issued it.
 */
final readonly class SdkCall
{
    public function __construct(
        public string $method,
        public string $path,
        public string $origin,
    ) {
    }

    public function key(): string
    {
        return $this->method.' '.$this->path;
    }
}
