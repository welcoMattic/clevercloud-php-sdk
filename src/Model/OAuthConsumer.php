<?php

namespace CleverCloud\Sdk\Model;

/**
 * An OAuth 1.0a consumer registered on the platform (an app that can drive the
 * 3-legged flow on behalf of users).
 */
final readonly class OAuthConsumer
{
    /**
     * @param list<string> $rights
     */
    public function __construct(
        public string $key,
        public ?string $secret = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $baseUrl = null,
        public ?string $picture = null,
        public array $rights = [],
    ) {
    }
}
