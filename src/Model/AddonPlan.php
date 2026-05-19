<?php

namespace CleverCloud\Sdk\Model;

final readonly class AddonPlan
{
    public function __construct(
        public string $id,
        public string $slug,
        public ?string $name = null,
    ) {
    }
}
