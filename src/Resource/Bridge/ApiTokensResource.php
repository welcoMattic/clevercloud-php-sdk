<?php

namespace CleverCloud\Sdk\Resource\Bridge;

use CleverCloud\Sdk\Model\ApiToken;
use CleverCloud\Sdk\Resource\AbstractBridgeResource;

/**
 * CRUD for Clever Cloud API tokens via `api-bridge.clever-cloud.com`.
 *
 * Authentication for this resource MUST be OAuth 1.0a, not a Bearer token.
 * The gateway validates the header shape and rejects anything else with
 * `HTTP 400 {"code":"FST_ERR_VALIDATION","message":"headers/authorization
 * Invalid input: must start with \"OAuth \""}`. That is the point of these
 * endpoints: you sign with your OAuth 1.0a consumer credentials in order to
 * mint a Bearer token, then use that token for the rest of the API. So an
 * `ApiToken`-authenticated client cannot manage tokens.
 */
final readonly class ApiTokensResource extends AbstractBridgeResource
{
    /**
     * @return list<ApiToken>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/api-tokens');

        return $this->mapCollection(ApiToken::class, $payload);
    }

    public function get(string $tokenId): ApiToken
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/api-tokens/'.rawurlencode($tokenId));

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
        $response = $this->httpPost('/api-tokens', ['json' => $payload]);

        return $this->mapTo(ApiToken::class, $response);
    }

    /**
     * @param array{name?: string, scopes?: list<string>} $payload
     */
    public function update(string $tokenId, array $payload): ApiToken
    {
        /** @var array<string, mixed> $response */
        $response = $this->httpPatch('/api-tokens/'.rawurlencode($tokenId), ['json' => $payload]);

        return $this->mapTo(ApiToken::class, $response);
    }

    public function delete(string $tokenId): void
    {
        $this->httpDelete('/api-tokens/'.rawurlencode($tokenId));
    }
}
