<?php

namespace CleverCloud\Sdk\Auth;

use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * Clever Cloud API token (Bearer) credentials.
 *
 * Issued via the api-bridge gateway (`https://api-bridge.clever-cloud.com`)
 * or the Console. Used as `Authorization: Bearer <token>` — no signing
 * involved. Build through {@see Credentials::apiToken()}.
 */
final readonly class ApiTokenCredentials extends Credentials
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {
    }

    public function applyTo(RequestInterface $request, OAuth1Signer $oauth1Signer): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer '.$this->token);
    }
}
