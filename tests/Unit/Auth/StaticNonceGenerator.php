<?php

namespace CleverCloud\Sdk\Tests\Unit\Auth;

use CleverCloud\Sdk\Auth\NonceGenerator;

final class StaticNonceGenerator implements NonceGenerator
{
    public function __construct(private readonly string $value)
    {
    }

    public function generate(): string
    {
        return $this->value;
    }
}
