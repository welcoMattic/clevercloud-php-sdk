<?php

namespace CleverCloud\Sdk;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Resource\Bridge\ApiTokensResource;
use CleverCloud\Sdk\Resource\V2\AddonsResource;
use CleverCloud\Sdk\Resource\V2\ApplicationsResource;
use CleverCloud\Sdk\Resource\V2\DeploymentsResource;
use CleverCloud\Sdk\Resource\V2\DomainsResource;
use CleverCloud\Sdk\Resource\V2\EnvironmentResource;
use CleverCloud\Sdk\Resource\V2\OrganisationsResource;
use CleverCloud\Sdk\Resource\V2\ProductsResource;
use CleverCloud\Sdk\Resource\V2\SelfResource;
use CleverCloud\Sdk\Resource\V2\TcpRedirectionsResource;
use CleverCloud\Sdk\Resource\V2\UsersResource;
use CleverCloud\Sdk\Resource\V4\BackupsResource;
use CleverCloud\Sdk\Resource\V4\BillingResource;
use CleverCloud\Sdk\Resource\V4\DrainsResource;
use CleverCloud\Sdk\Resource\V4\InstancesResource;
use CleverCloud\Sdk\Resource\V4\LoadBalancersResource;
use CleverCloud\Sdk\Resource\V4\LogsResource;
use CleverCloud\Sdk\Resource\V4\NetworkGroupsResource;
use CleverCloud\Sdk\Resource\V4\NotificationsResource;
use CleverCloud\Sdk\Resource\V4\OperatorsResource;
use CleverCloud\Sdk\Resource\V4\OrchestrationResource;
use CleverCloud\Sdk\Resource\V4\PulsarPoliciesResource;
use CleverCloud\Sdk\Resource\V4\WebhooksResource;
use CleverCloud\Sdk\Resource\V4\ZonesResource;

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
    private ?BillingResource $billingResource = null;
    private ?InstancesResource $instancesResource = null;
    private ?LoadBalancersResource $loadBalancersResource = null;
    private ?ProductsResource $productsResource = null;
    private ?ZonesResource $zonesResource = null;
    private ?PulsarPoliciesResource $pulsarPoliciesResource = null;
    private ?LogsResource $logsResource = null;
    private ?OperatorsResource $operatorsResource = null;
    private ?DrainsResource $drainsResource = null;
    private ?NotificationsResource $notificationsResource = null;
    private ?WebhooksResource $webhooksResource = null;
    private ?NetworkGroupsResource $networkGroupsResource = null;
    private ?OrchestrationResource $orchestrationResource = null;
    private ?ApiTokensResource $apiTokensResource = null;
    private ?TcpRedirectionsResource $tcpRedirectionsResource = null;
    private ?BackupsResource $backupsResource = null;

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

    public BillingResource $billing {
        get => $this->billingResource ??= new BillingResource($this->http, $this->mapper);
    }

    public InstancesResource $instances {
        get => $this->instancesResource ??= new InstancesResource($this->http, $this->mapper);
    }

    public LoadBalancersResource $loadBalancers {
        get => $this->loadBalancersResource ??= new LoadBalancersResource($this->http, $this->mapper);
    }

    public ProductsResource $products {
        get => $this->productsResource ??= new ProductsResource($this->http, $this->mapper);
    }

    public ZonesResource $zones {
        get => $this->zonesResource ??= new ZonesResource($this->http, $this->mapper);
    }

    public PulsarPoliciesResource $pulsarPolicies {
        get => $this->pulsarPoliciesResource ??= new PulsarPoliciesResource($this->http, $this->mapper);
    }

    public LogsResource $logs {
        get => $this->logsResource ??= new LogsResource($this->http, $this->mapper);
    }

    public OperatorsResource $operators {
        get => $this->operatorsResource ??= new OperatorsResource($this->http, $this->mapper);
    }

    public DrainsResource $drains {
        get => $this->drainsResource ??= new DrainsResource($this->http, $this->mapper);
    }

    public NotificationsResource $notifications {
        get => $this->notificationsResource ??= new NotificationsResource($this->http, $this->mapper);
    }

    public WebhooksResource $webhooks {
        get => $this->webhooksResource ??= new WebhooksResource($this->http, $this->mapper);
    }

    public NetworkGroupsResource $networkGroups {
        get => $this->networkGroupsResource ??= new NetworkGroupsResource($this->http, $this->mapper);
    }

    public OrchestrationResource $orchestration {
        get => $this->orchestrationResource ??= new OrchestrationResource($this->http, $this->mapper);
    }

    public ApiTokensResource $apiTokens {
        get => $this->apiTokensResource ??= new ApiTokensResource($this->http, $this->mapper);
    }

    public TcpRedirectionsResource $tcpRedirections {
        get => $this->tcpRedirectionsResource ??= new TcpRedirectionsResource($this->http, $this->mapper);
    }

    public BackupsResource $backups {
        get => $this->backupsResource ??= new BackupsResource($this->http, $this->mapper);
    }
}
