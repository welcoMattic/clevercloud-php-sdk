<?php

namespace App\Service;

use App\Exception\NotAuthenticatedException;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\ClientBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds a fully-authenticated `CleverCloud\Sdk\Client` from:
 *
 *   - the consumer key / secret pinned in env vars (.env.local)
 *   - the user token / secret stored in the session after a successful
 *     OAuth 1.0a 3-legged login (see {@see \App\Controller\SecurityController})
 *
 * Throws {@see NotAuthenticatedException} when the session has no user
 * token yet; the matching exception listener redirects to /login.
 */
final class ClevercloudClientFactory
{
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

    public function create(): Client
    {
        $session = $this->requestStack->getSession();
        $token = $session->get(self::SESSION_TOKEN);
        $tokenSecret = $session->get(self::SESSION_TOKEN_SECRET);

        if (!\is_string($token) || !\is_string($tokenSecret) || '' === $token || '' === $tokenSecret) {
            throw new NotAuthenticatedException('No Clever Cloud user token in session — log in first.');
        }

        return (new ClientBuilder())
            ->withCredentials(new Credentials(
                consumerKey: $this->consumerKey,
                consumerSecret: $this->consumerSecret,
                token: $token,
                tokenSecret: $tokenSecret,
            ))
            ->build();
    }
}
