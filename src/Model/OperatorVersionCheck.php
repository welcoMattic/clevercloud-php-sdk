<?php

namespace CleverCloud\Sdk\Model;

/**
 * Whether a Keycloak, Metabase or Otoroshi add-on can move to a newer
 * version. Returned by
 * `/v4/addon-providers/addon-{kind}/addons/{id}/version/check`.
 */
final readonly class OperatorVersionCheck
{
    /**
     * @param list<string> $available                 versions on offer
     * @param bool|null    $requiresApplicationUpdate Metabase only: whether moving to `latest` also resizes the add-on's application
     */
    public function __construct(
        public string $installed,
        public string $latest,
        public bool $needUpdate,
        public array $available = [],
        public ?bool $requiresApplicationUpdate = null,
    ) {
    }
}
