<?php

namespace CleverCloud\Sdk\Model;

/**
 * An OAuth 1.0a access token issued to the current user, as returned by
 * `/v2/self/tokens` and `/v2/self/consumers/{key}/tokens`. Dates are
 * milliseconds since the Unix epoch.
 *
 * `consumer` stays a raw array (`{key, name, description, url, baseUrl,
 * picture, rights}`) and `rights` is the map of OAuth right names to booleans
 * (`almighty`, `manage_ssh_keys`, ...).
 */
final readonly class OAuthAccessToken
{
    /**
     * @param array<string, mixed> $consumer
     * @param array<string, bool>  $rights
     */
    public function __construct(
        public string $token,
        public array $consumer = [],
        public array $rights = [],
        public ?int $creationDate = null,
        public ?int $expirationDate = null,
        public ?int $lastUtilisation = null,
        public ?string $employeeId = null,
    ) {
    }
}
