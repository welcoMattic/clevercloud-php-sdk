<?php

namespace CleverCloud\Sdk\Model;

/**
 * An OAuth 1.0a consumer registered on the platform (an app that can drive the
 * 3-legged flow on behalf of users). The secret is not part of this payload:
 * read it with `consumerSecret()`.
 */
final readonly class OAuthConsumer
{
    /**
     * @param array<string, bool> $rights right name (`access_organisations`, `manage_ssh_keys`, ...) to whether it is granted
     */
    public function __construct(
        public string $key,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $baseUrl = null,
        public ?string $picture = null,
        public array $rights = [],
    ) {
    }
}
