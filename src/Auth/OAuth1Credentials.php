<?php

namespace CleverCloud\Sdk\Auth;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Configuration;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
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

    /**
     * OAuth 1.0a endpoints use api.clever-cloud.com, but logs are only available
     * on console.clever-cloud.com. Rewrite logs paths to use the console host.
     */
    public function rewriteUri(\Psr\Http\Message\UriInterface $uri, ApiVersion $version, Configuration $configuration): \Psr\Http\Message\UriInterface
    {
        $path = (string) $uri->getPath();
        
        // Logs endpoints must go through console.clever-cloud.com
        if (str_contains($path, '/applications/') && str_ends_with($path, '/logs')) {
            return $uri
                ->withScheme('https')
                ->withHost('console.clever-cloud.com');
        }
        
        // All other OAuth endpoints use api.clever-cloud.com (default from UriBuilder)
        return $uri;
    }
}
