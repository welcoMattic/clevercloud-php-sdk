<?php

namespace CleverCloud\Sdk\Resource\Bridge;

use CleverCloud\Sdk\Model\ApiToken;
use CleverCloud\Sdk\Resource\AbstractBridgeResource;

/**
 * CRUD for Clever Cloud API tokens via `api-bridge.clever-cloud.com`.
 *
 * Authentication for this resource MUST itself be a Bearer token — typically
 * a long-lived token created from the Console, then used to mint and revoke
 * short-lived ones. The SDK doesn't enforce that; if you call these endpoints
 * with OAuth1 credentials, the gateway will return 401.
 */
final readonly class ApiTokensResource extends AbstractBridgeResource
{
    /**
     * @return list<ApiToken>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/v2/api-tokens');

        return $this->mapCollection(ApiToken::class, $payload);
    }

    public function get(string $tokenId): ApiToken
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/v2/api-tokens/'.rawurlencode($tokenId));

        return $this->mapTo(ApiToken::class, $payload);
    }

    /**
     * Mint a new API token. The plaintext `token` is included in the response
     * **only on creation** — store it immediately, you can't retrieve it later.
     *
     * @param array{name: string, scopes?: list<string>, expires_at?: string|null} $payload
     */
    public function create(array $payload): ApiToken
    {
        /** @var array<string, mixed> $response */
        $response = $this->httpPost('/v2/api-tokens', ['json' => $payload]);

        return $this->mapTo(ApiToken::class, $response);
    }

    /**
     * @param array{name?: string, scopes?: list<string>} $payload
     */
    public function update(string $tokenId, array $payload): ApiToken
    {
        /** @var array<string, mixed> $response */
        $response = $this->httpPatch('/v2/api-tokens/'.rawurlencode($tokenId), ['json' => $payload]);

        return $this->mapTo(ApiToken::class, $response);
    }

    public function delete(string $tokenId): void
    {
        $this->httpDelete('/v2/api-tokens/'.rawurlencode($tokenId));
    }
}
