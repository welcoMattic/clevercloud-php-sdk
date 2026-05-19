<?php

namespace CleverCloud\Sdk\Resource\V4;

use AutoMapper\AutoMapperInterface;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Resource\V4\Operator\KeycloakResource;
use CleverCloud\Sdk\Resource\V4\Operator\MatomoResource;
use CleverCloud\Sdk\Resource\V4\Operator\MetabaseResource;
use CleverCloud\Sdk\Resource\V4\Operator\OtoroshiResource;

/**
 * Facade exposing the four operator add-ons (Keycloak, Matomo, Metabase,
 * Otoroshi) via lazily-instantiated sub-resources.
 *
 * Doesn't extend {@see AbstractV4Resource} on purpose — it owns no endpoint
 * of its own, just routes callers to the typed sub-clients via property hooks.
 */
final class OperatorsResource
{
    private ?KeycloakResource $keycloakResource = null;
    private ?MatomoResource $matomoResource = null;
    private ?MetabaseResource $metabaseResource = null;
    private ?OtoroshiResource $otoroshiResource = null;

    public function __construct(
        private readonly HttpClient $http,
        private readonly AutoMapperInterface $mapper,
    ) {
    }

    public KeycloakResource $keycloak {
        get => $this->keycloakResource ??= new KeycloakResource($this->http, $this->mapper);
    }

    public MatomoResource $matomo {
        get => $this->matomoResource ??= new MatomoResource($this->http, $this->mapper);
    }

    public MetabaseResource $metabase {
        get => $this->metabaseResource ??= new MetabaseResource($this->http, $this->mapper);
    }

    public OtoroshiResource $otoroshi {
        get => $this->otoroshiResource ??= new OtoroshiResource($this->http, $this->mapper);
    }
}
