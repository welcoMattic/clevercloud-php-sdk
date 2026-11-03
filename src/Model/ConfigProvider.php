<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A configuration provider add-on with the environment variables it holds.
 * Returned by `/v4/addon-providers/config-provider/addons/{addonId}`.
 */
final readonly class ConfigProvider
{
    /**
     * @param array<string, string> $env environment variables, keyed by name
     */
    public function __construct(
        public string $id,
        #[MapFrom(property: 'creation_date')]
        public ?string $creationDate = null,
        #[MapFrom(property: 'owner_id')]
        public ?string $ownerId = null,
        public array $env = [],
    ) {
    }
}
