<?php

namespace CleverCloud\Sdk\Auth;

final class RandomNonceGenerator implements NonceGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
