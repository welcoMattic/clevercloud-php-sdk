<?php

namespace CleverCloud\Sdk\Auth;

use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Exception\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Drives the OAuth 1.0a three-legged flow against Clever Cloud:
 *
 *   1. {@see requestToken()} — POST /v2/oauth/request_token with the consumer
 *      credentials, get a temporary request token + secret
 *   2. {@see authorizationUrl()} — redirect the user to authorise the request
 *      token; Clever Cloud sends them back to your callback with `oauth_verifier`
 *   3. {@see accessToken()} — POST /v2/oauth/access_token with the verifier, get
 *      the long-lived user token + secret you'll then pass to {@see Credentials}
 *
 * The flow speaks `application/x-www-form-urlencoded` rather than JSON, so we
 * skip the rest of the SDK's HttpClient stack and drive PSR-18 directly.
 */
final readonly class OAuthFlow
{
    public function __construct(
        private OAuth1Signer $signer,
        private ClientInterface $psr18,
        private RequestFactoryInterface $requestFactory,
        private Configuration $configuration = new Configuration(),
    ) {
    }

    /**
     * @return array{token: string, tokenSecret: string}
     */
    public function requestToken(string $consumerKey, string $consumerSecret, string $callbackUrl): array
    {
        $credentials = new Credentials($consumerKey, $consumerSecret);
        $body = $this->dispatch(
            'POST',
            $this->configuration->v2BaseUrl.'/oauth/request_token',
            $credentials,
            ['oauth_callback' => $callbackUrl],
        );

        return [
            'token' => $body['oauth_token'] ?? throw new ApiException('Missing oauth_token in request_token response', 0),
            'tokenSecret' => $body['oauth_token_secret'] ?? throw new ApiException('Missing oauth_token_secret in request_token response', 0),
        ];
    }

    public function authorizationUrl(string $requestToken): string
    {
        return $this->configuration->v2BaseUrl.'/oauth/authorize?oauth_token='.rawurlencode($requestToken);
    }

    /**
     * @return array{token: string, tokenSecret: string}
     */
    public function accessToken(
        string $consumerKey,
        string $consumerSecret,
        string $requestToken,
        string $requestTokenSecret,
        string $verifier,
    ): array {
        $credentials = new Credentials($consumerKey, $consumerSecret, $requestToken, $requestTokenSecret);
        $body = $this->dispatch(
            'POST',
            $this->configuration->v2BaseUrl.'/oauth/access_token',
            $credentials,
            ['oauth_verifier' => $verifier],
        );

        return [
            'token' => $body['oauth_token'] ?? throw new ApiException('Missing oauth_token in access_token response', 0),
            'tokenSecret' => $body['oauth_token_secret'] ?? throw new ApiException('Missing oauth_token_secret in access_token response', 0),
        ];
    }

    /**
     * @param array<string, string> $extraOAuthParams
     *
     * @return array<string, string>
     */
    private function dispatch(string $method, string $url, Credentials $credentials, array $extraOAuthParams): array
    {
        $request = $this->requestFactory
            ->createRequest($method, $url)
            ->withHeader('User-Agent', $this->configuration->userAgent);

        $signed = $this->signer->sign($request, $credentials, $extraOAuthParams);

        try {
            $response = $this->psr18->sendRequest($signed);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('OAuth flow transport error: '.$e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new ApiException(\sprintf('OAuth flow request failed: HTTP %d — %s', $status, $body), $status);
        }

        $parsed = [];
        parse_str($body, $parsed);

        /** @var array<string, string> $parsed */
        return $parsed;
    }
}
