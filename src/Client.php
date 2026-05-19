<?php

namespace CleverCloud\Sdk;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Resource\V2\AddonsResource;
use CleverCloud\Sdk\Resource\V2\ApplicationsResource;
use CleverCloud\Sdk\Resource\V2\DeploymentsResource;
use CleverCloud\Sdk\Resource\V2\DomainsResource;
use CleverCloud\Sdk\Resource\V2\EnvironmentResource;
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
    private ?ApplicationsResource $applicationsResource = null;
    private ?AddonsResource $addonsResource = null;
    private ?DeploymentsResource $deploymentsResource = null;
    private ?EnvironmentResource $environmentResource = null;
    private ?DomainsResource $domainsResource = null;

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

    public ApplicationsResource $applications {
        get => $this->applicationsResource ??= new ApplicationsResource($this->http, $this->mapper);
    }

    public AddonsResource $addons {
        get => $this->addonsResource ??= new AddonsResource($this->http, $this->mapper);
    }

    public DeploymentsResource $deployments {
        get => $this->deploymentsResource ??= new DeploymentsResource($this->http, $this->mapper);
    }

    public EnvironmentResource $environment {
        get => $this->environmentResource ??= new EnvironmentResource($this->http, $this->mapper);
    }

    public DomainsResource $domains {
        get => $this->domainsResource ??= new DomainsResource($this->http, $this->mapper);
    }
}
