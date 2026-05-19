<?php

namespace CleverCloud\Sdk;

use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Resource\V2\SelfResource;

/**
 * Top-level Clever Cloud SDK facade. Build with {@see ClientBuilder}.
 *
 * Each resource is exposed via a property hook so it's lazily instantiated the
 * first time it's read.
 */
final class Client
{
    private ?SelfResource $selfResource = null;

    public function __construct(public readonly HttpClient $http)
    {
    }

    public SelfResource $self {
        get => $this->selfResource ??= new SelfResource($this->http);
    }
}
