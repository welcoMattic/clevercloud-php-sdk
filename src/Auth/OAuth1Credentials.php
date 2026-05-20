<?php

namespace CleverCloud\Sdk\Auth;

use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * OAuth 1.0a credentials used to sign every request with HMAC-SHA512.
 *
 * The token / tokenSecret pair is optional only during the two-legged
 * `request_token` step of the 3-legged OAuth flow; every authenticated call
 * needs both. Build through {@see Credentials::oauth1()} rather than calling
 * `new` directly.
 */
final readonly class OAuth1Credentials extends Credentials
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

    public function applyTo(RequestInterface $request, OAuth1Signer $oauth1Signer): RequestInterface
    {
        return $oauth1Signer->sign($request, $this);
    }
}
