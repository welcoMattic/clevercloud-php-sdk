<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A log drain — forwards application or add-on logs to an external sink
 * (Datadog, ElasticSearch, NewRelic, syslog, raw HTTP, etc.).
 */
final readonly class Drain
{
    /**
     * @param array<string, mixed> $credentials
     */
    public function __construct(
        public string $id,
        #[MapFrom(property: 'state')]
        public ?string $state = null,
        public ?string $kind = null,
        public ?string $url = null,
        public ?string $name = null,
        public ?string $resourceId = null,
        public ?bool $enabled = null,
        public array $credentials = [],
    ) {
    }
}
