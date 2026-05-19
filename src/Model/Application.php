<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud application, as returned by `/v2/{owner}/applications[/{id}]`.
 *
 * The richest sub-objects (`instance`, `deployment`, `buildFlavor`) stay as raw
 * arrays for now — they have a lot of fields and Phase 6 hardens them into
 * dedicated DTOs once we've stabilised the public API surface.
 */
final readonly class Application
{
    /**
     * @param list<Vhost>          $vhosts
     * @param array<string, mixed> $instance
     * @param array<string, mixed> $deployment
     * @param array<string, mixed> $buildFlavor
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $zone = null,
        public ?string $branch = null,
        public ?string $ownerId = null,
        public ?string $state = null,
        public ?string $commitId = null,
        public ?string $webhookUrl = null,
        public ?bool $archived = null,
        public ?bool $favourite = null,
        public ?bool $homogeneous = null,
        public ?bool $cancelOnPush = null,
        public ?bool $separateBuild = null,
        public ?bool $stickySessions = null,
        public ?int $creationDate = null,
        public ?int $lastDeploy = null,
        public array $vhosts = [],
        public array $instance = [],
        public array $deployment = [],
        public array $buildFlavor = [],
    ) {
    }
}
