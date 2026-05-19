<?php

namespace CleverCloud\Sdk\Model;

final readonly class PaymentMethod
{
    public function __construct(
        public string $id,
        public ?string $type = null,
        public ?string $name = null,
        public ?bool $isDefault = null,
        public ?bool $isExpired = null,
        public ?int $createdAt = null,
    ) {
    }
}
