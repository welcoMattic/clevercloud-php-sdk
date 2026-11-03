<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\CreatedServiceToken;
use CleverCloud\Sdk\Model\Enum\ServiceTokenRole;
use CleverCloud\Sdk\Model\ServiceToken;
use CleverCloud\Sdk\Model\ServiceTokenPage;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Organisation service tokens (biscuits) under
 * `/v2/organisations/{id}/service-tokens`.
 *
 * The API declares these four routes as OAuth 1.0a only: a client
 * authenticated with a service token cannot manage service tokens. Use the
 * minted value with {@see \CleverCloud\Sdk\Auth\Credentials::serviceToken()}.
 */
final readonly class ServiceTokensResource extends AbstractV2Resource
{
    /**
     * Every service token of the organisation (newest first), as one array.
     * A member without the `ADMIN` or `MANAGER` role only sees the tokens
     * they created. The API refuses this form past 10 000 tokens: use
     * {@see listPage()} then.
     *
     * @param int|null $limit  keep at most that many entries (all when null)
     * @param int|null $offset skip that many entries before `$limit` applies
     *
     * @return list<ServiceToken>
     */
    public function list(string $organisationId, ?int $limit = null, ?int $offset = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->basePath($organisationId), [
            'query' => ['limit' => $limit, 'offset' => $offset],
        ]);

        return $this->mapCollection(ServiceToken::class, $payload);
    }

    /**
     * One cursor page of service tokens (`paginate=1`). Pass the returned
     * `nextCursor` back as `$cursor` until it is `null`.
     *
     * @param int|null    $limit  page size (50 by default, 200 at most)
     * @param string|null $cursor `nextCursor` of the previous page, `null` for the first one
     */
    public function listPage(string $organisationId, ?int $limit = null, ?string $cursor = null): ServiceTokenPage
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($organisationId), [
            'query' => ['paginate' => 1, 'limit' => $limit, 'cursor' => $cursor],
        ]);

        return $this->mapTo(ServiceTokenPage::class, $payload);
    }

    /**
     * Mint a service token acting on the organisation's behalf with `$role`.
     * The token value is returned **only in this response**: store it
     * immediately.
     *
     * @param list<string>|null $resources  application (`app_...`) and add-on (`addon_...`) IDs the
     *                                      token is restricted to; null or empty gives access to the
     *                                      whole organisation
     * @param int|null          $ttlSeconds lifetime in seconds (90 days by default, one year at most)
     */
    public function create(
        string $organisationId,
        string $name,
        ServiceTokenRole $role,
        ?string $description = null,
        ?array $resources = null,
        ?int $ttlSeconds = null,
    ): CreatedServiceToken {
        $body = ['name' => $name, 'role' => $role->value];
        if (null !== $description) {
            $body['description'] = $description;
        }
        if (null !== $resources) {
            $body['resources'] = $resources;
        }
        if (null !== $ttlSeconds) {
            $body['ttl_seconds'] = $ttlSeconds;
        }

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->basePath($organisationId), ['json' => $body]);

        return $this->mapTo(CreatedServiceToken::class, $payload);
    }

    /**
     * @param string $tokenId the token ID or its revocation ID
     */
    public function get(string $organisationId, string $tokenId): ServiceToken
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($organisationId).'/'.rawurlencode($tokenId));

        return $this->mapTo(ServiceToken::class, $payload);
    }

    /**
     * Revoke the token: it stops being accepted right away.
     *
     * @param string $tokenId the token ID or its revocation ID
     */
    public function delete(string $organisationId, string $tokenId): void
    {
        $this->httpDelete($this->basePath($organisationId).'/'.rawurlencode($tokenId));
    }

    private function basePath(string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/service-tokens';
    }
}
