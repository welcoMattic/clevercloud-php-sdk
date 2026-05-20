<?php

namespace CleverCloud\Sdk\Auth;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Configuration;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;

/**
 * Base type for authentication credentials. Build via the named constructors:
 *
 *   Credentials::oauth1('ck', 'cs', 'tk', 'ts');
 *   Credentials::apiToken('cc_eyJhbGciOi...');
 *
 * The chosen credential type drives which `Authorization` header the SDK
 * attaches to outgoing requests. Bearer (API token) is the auth mode Clever
 * Cloud now recommends; OAuth 1.0a is kept for legacy consumers and the
 * 3-legged authorisation flow.
 */
abstract readonly class Credentials
{
    public static function oauth1(
        string $consumerKey,
        #[SensitiveParameter]
        string $consumerSecret,
        ?string $token = null,
        #[SensitiveParameter]
        ?string $tokenSecret = null,
    ): OAuth1Credentials {
        return new OAuth1Credentials($consumerKey, $consumerSecret, $token, $tokenSecret);
    }

    public static function apiToken(
        #[SensitiveParameter]
        string $token,
    ): ApiTokenCredentials {
        return new ApiTokenCredentials($token);
    }

    /**
     * Attach the proper `Authorization` header (or signature) to `$request`
     * and return the resulting message. Called once per request by the
     * HTTP client just before dispatch.
     */
    abstract public function applyTo(RequestInterface $request, OAuth1Signer $oauth1Signer): RequestInterface;

    /**
     * Last-chance hook to rewrite the request URI based on the credential
     * type — e.g. API token credentials route all V2/V4 paths through the
     * `api-bridge.clever-cloud.com` gateway. Default: identity.
     */
    public function rewriteUri(UriInterface $uri, ApiVersion $version, Configuration $configuration): UriInterface
    {
        return $uri;
    }
}
