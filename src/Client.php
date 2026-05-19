<?php

namespace CleverCloud\Sdk;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Resource\V2\OrganisationsResource;
use CleverCloud\Sdk\Resource\V2\SelfResource;
use CleverCloud\Sdk\Resource\V2\UsersResource;

/**
 * Top-level Clever Cloud SDK facade. Build with {@see ClientBuilder}.
 *
 * Each resource is exposed via a property hook so it's lazily instantiated the
 * first time it's read.
 */
final class Client
{
    private ?SelfResource $selfResource = null;
    private ?UsersResource $usersResource = null;
    private ?OrganisationsResource $organisationsResource = null;

    public function __construct(
        public readonly HttpClient $http,
        public readonly AutoMapperInterface $mapper,
    ) {
    }

    public SelfResource $self {
        get => $this->selfResource ??= new SelfResource($this->http, $this->mapper);
    }

    public UsersResource $users {
        get => $this->usersResource ??= new UsersResource($this->http, $this->mapper);
    }

    public OrganisationsResource $organisations {
        get => $this->organisationsResource ??= new OrganisationsResource($this->http, $this->mapper);
    }
}
