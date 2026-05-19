<?php

namespace CleverCloud\Sdk;

final readonly class Configuration
{
    public const string DEFAULT_V2_BASE_URL = 'https://api.clever-cloud.com/v2';
    public const string DEFAULT_V4_BASE_URL = 'https://api.clever-cloud.com/v4';
    public const string DEFAULT_USER_AGENT = 'clevercloud-sdk-php';

    public function __construct(
        public string $v2BaseUrl = self::DEFAULT_V2_BASE_URL,
        public string $v4BaseUrl = self::DEFAULT_V4_BASE_URL,
        public string $userAgent = self::DEFAULT_USER_AGENT,
        public int $timeoutSeconds = 30,
    ) {
    }

    public function baseUrlFor(ApiVersion $version): string
    {
        return match ($version) {
            ApiVersion::V2 => $this->v2BaseUrl,
            ApiVersion::V4 => $this->v4BaseUrl,
        };
    }
}
