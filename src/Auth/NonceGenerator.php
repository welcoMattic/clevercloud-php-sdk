<?php

namespace CleverCloud\Sdk\Auth;

interface NonceGenerator
{
    /**
     * Returns a unique, opaque token suitable for the OAuth `oauth_nonce` field.
     */
    public function generate(): string;
}
