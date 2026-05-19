<?php

namespace CleverCloud\Sdk\Model;

final readonly class Addon
{
    /**
     * @param list<string> $configKeys
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $realId = null,
        public ?string $region = null,
        public ?int $creationDate = null,
        public ?AddonProvider $provider = null,
        public ?AddonPlan $plan = null,
        public array $configKeys = [],
    ) {
    }
}
