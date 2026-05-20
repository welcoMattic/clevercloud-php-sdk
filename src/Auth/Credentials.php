<?php

namespace CleverCloud\Sdk\Auth;

use Psr\Http\Message\RequestInterface;
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
}
