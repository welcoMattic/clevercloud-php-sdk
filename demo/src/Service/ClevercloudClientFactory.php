<?php

namespace App\Service;

use App\Exception\NotAuthenticatedException;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\ClientBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds a fully-authenticated `CleverCloud\Sdk\Client` from one of two
 * credential sources:
 *
 *   1. an **API token** (Bearer) pasted at `/login/token`, stored in session
 *      key {@see self::SESSION_API_TOKEN};
 *   2. the **OAuth 1.0a** user token / secret obtained at `/oauth/callback`
 *      after the 3-legged flow against the consumer pair pinned in env vars.
 *
 * API token wins when both are present. Throws
 * {@see NotAuthenticatedException} when no credentials are in session yet;
 * the matching exception listener redirects to `/login`.
 */
final class ClevercloudClientFactory
{
    public const string SESSION_API_TOKEN = 'cc_api_token';
    public const string SESSION_TOKEN = 'cc_user_token';
    public const string SESSION_TOKEN_SECRET = 'cc_user_token_secret';

    public function __construct(
        #[\SensitiveParameter] private readonly string $consumerKey,
        #[\SensitiveParameter] private readonly string $consumerSecret,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function consumerKey(): string
    {
        return $this->consumerKey;
    }

    public function consumerSecret(): string
    {
        return $this->consumerSecret;
    }

    /**
     * Returns the auth mode currently active in session: 'api-token', 'oauth1',
     * or null when no credentials are stored.
     */
    public function authMode(): ?string
    {
        $session = $this->requestStack->getSession();

        if (\is_string($session->get(self::SESSION_API_TOKEN)) && '' !== $session->get(self::SESSION_API_TOKEN)) {
            return 'api-token';
        }

        $token = $session->get(self::SESSION_TOKEN);
        $secret = $session->get(self::SESSION_TOKEN_SECRET);
        if (\is_string($token) && '' !== $token && \is_string($secret) && '' !== $secret) {
            return 'oauth1';
        }

        return null;
    }

    public function create(): Client
    {
        $session = $this->requestStack->getSession();

        $apiToken = $session->get(self::SESSION_API_TOKEN);
        if (\is_string($apiToken) && '' !== $apiToken) {
            return (new ClientBuilder())
                ->withCredentials(Credentials::apiToken($apiToken))
                ->build();
        }

        $token = $session->get(self::SESSION_TOKEN);
        $tokenSecret = $session->get(self::SESSION_TOKEN_SECRET);
        if (\is_string($token) && \is_string($tokenSecret) && '' !== $token && '' !== $tokenSecret) {
            return (new ClientBuilder())
                ->withCredentials(Credentials::oauth1(
                    consumerKey: $this->consumerKey,
                    consumerSecret: $this->consumerSecret,
                    token: $token,
                    tokenSecret: $tokenSecret,
                ))
                ->build();
        }

        throw new NotAuthenticatedException('No Clever Cloud credentials in session — log in first.');
    }
}
