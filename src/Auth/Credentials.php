<?php

namespace CleverCloud\Sdk\Auth;

use SensitiveParameter;

/**
 * OAuth 1.0a credentials used to sign every request to Clever Cloud.
 *
 * The token / tokenSecret pair is optional only during the two-legged
 * `request_token` step of the OAuth flow; every authenticated call needs both.
 */
final readonly class Credentials
{
    public function __construct(
        public string $consumerKey,
        #[SensitiveParameter]
        public string $consumerSecret,
        public ?string $token = null,
        #[SensitiveParameter]
        public ?string $tokenSecret = null,
    ) {
    }

    public function hasUserToken(): bool
    {
        return null !== $this->token && null !== $this->tokenSecret;
    }
}
