<?php

namespace CleverCloud\Sdk\Auth;

use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * Clever Cloud service token (biscuit) credentials.
 *
 * Service tokens are minted per organisation through
 * `POST /v2/organisations/{id}/service-tokens` and sent as
 * `Authorization: Bearer <token>` straight to `api.clever-cloud.com`: unlike
 * {@see ApiTokenCredentials}, the request URI is left untouched (no reroute to
 * the api-bridge gateway). Build through {@see Credentials::serviceToken()}.
 *
 * The API only accepts them on the routes whose security declares the
 * `biscuit` scheme (organisation applications, network groups, Kubernetes
 * node groups, ...), never under `/v2/self`.
 */
final readonly class ServiceTokenCredentials extends Credentials
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
