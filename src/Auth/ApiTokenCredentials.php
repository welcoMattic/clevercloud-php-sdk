<?php

namespace CleverCloud\Sdk\Auth;

use CleverCloud\Sdk\ApiVersion;
use CleverCloud\Sdk\Configuration;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
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

    /**
     * API tokens are only honoured by the api-bridge gateway. Route every V2
     * and V4 call to `api-bridge.clever-cloud.com` while preserving the path
     * (the bridge proxies the same `/v2/...` and `/v4/...` namespaces).
     */
    public function rewriteUri(UriInterface $uri, ApiVersion $version, Configuration $configuration): UriInterface
    {
        if (ApiVersion::Bridge === $version) {
            return $uri;
        }

        $bridge = parse_url($configuration->bridgeBaseUrl);
        if (!\is_array($bridge) || !isset($bridge['host'])) {
            return $uri;
        }

        $uri = $uri
            ->withScheme($bridge['scheme'] ?? 'https')
            ->withHost($bridge['host']);

        if (isset($bridge['port'])) {
            $uri = $uri->withPort($bridge['port']);
        }

        return $uri;
    }
}
