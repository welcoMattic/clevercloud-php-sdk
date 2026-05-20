<?php

namespace CleverCloud\Sdk\Model;

final readonly class EmailAddress
{
    public function __construct(
        public string $email,
        public ?bool $primary = null,
        public ?bool $validated = null,
    ) {
    }
}
