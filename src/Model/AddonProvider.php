<?php

namespace CleverCloud\Sdk\Model;

/**
 * Identifies an add-on provider (PostgreSQL, Redis, …).
 */
final readonly class AddonProvider
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $website = null,
        public ?string $shortDesc = null,
        public ?string $longDesc = null,
    ) {
    }
}
