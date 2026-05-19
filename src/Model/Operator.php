<?php

namespace CleverCloud\Sdk\Model;

/**
 * Common DTO for the four "operator"-style add-ons Clever Cloud manages:
 * Keycloak, Matomo, Metabase, Otoroshi. Returned by
 * `/v4/operators/{kind}[/{id}]`.
 */
final readonly class Operator
{
    /**
     * @param array<string, mixed> $features
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $region = null,
        public ?string $resourceId = null,
        public ?string $status = null,
        public ?string $kind = null,
        public ?int $creationDate = null,
        public array $features = [],
    ) {
    }
}
