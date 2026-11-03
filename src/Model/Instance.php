<?php

namespace CleverCloud\Sdk\Model;

/**
 * A running instance, as returned by
 * `/v2/organisations/{id}/addons/{addonId}/instances[/{instanceId}]`.
 *
 * `flavor` stays a raw array (`{name, mem, cpus, price}`).
 */
final readonly class Instance
{
    /**
     * @param array<string, mixed>|null $flavor
     */
    public function __construct(
        public string $id,
        public string $appId,
        public string $state,
        public ?string $displayName = null,
        public ?string $ip = null,
        public ?int $appPort = null,
        public ?string $commit = null,
        public ?string $deployId = null,
        public ?int $deployNumber = null,
        public ?int $instanceNumber = null,
        public ?int $creationDate = null,
        public ?array $flavor = null,
    ) {
    }
}
