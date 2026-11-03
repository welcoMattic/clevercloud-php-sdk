<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * Common DTO for the four "operator"-style add-ons Clever Cloud manages:
 * Keycloak, Matomo, Metabase, Otoroshi. Returned by
 * `/v4/addon-providers/addon-{kind}/addons/{id}`.
 *
 * The API sends no `id` field: `$id` is read from `resourceId`, the
 * operator id (`keycloak_…`, `matomo_…`, …) that the operator endpoints take
 * as path parameter. `$addonId` is the matching `addon_…` id used by the V2
 * add-on endpoints.
 */
final readonly class Operator
{
    /**
     * @param array<string, mixed> $features
     */
    public function __construct(
        #[MapFrom(property: 'resourceId')]
        public string $id,
        public string $name,
        public ?string $resourceId = null,
        public array $features = [],
        public ?string $addonId = null,
        public ?string $ownerId = null,
        public ?string $version = null,
        public ?string $accessUrl = null,
    ) {
    }
}
