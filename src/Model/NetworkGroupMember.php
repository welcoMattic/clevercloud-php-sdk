<?php

namespace CleverCloud\Sdk\Model;

final readonly class NetworkGroupMember
{
    public function __construct(
        public string $id,
        public ?string $kind = null,
        public ?string $label = null,
        public ?string $domainName = null,
    ) {
    }
}
