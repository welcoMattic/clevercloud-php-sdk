<?php

namespace CleverCloud\Sdk\Model;

final readonly class Country
{
    public function __construct(
        public string $code,
        public ?string $name = null,
        public ?bool $eu = null,
    ) {
    }
}
