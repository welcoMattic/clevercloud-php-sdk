<?php

namespace CleverCloud\Sdk\Model;

final readonly class SshKey
{
    public function __construct(
        public string $name,
        public ?string $key = null,
        public ?string $fingerprint = null,
    ) {
    }
}
