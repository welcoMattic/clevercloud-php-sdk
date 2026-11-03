<?php

namespace CleverCloud\Sdk\Model;

/**
 * Platform-wide Network Group settings, as returned by
 * `GET /networkgroup/configuration`.
 */
final readonly class NetworkGroupsConfiguration
{
    /**
     * @param list<string> $dnsSuffixes a member `domainName` must end with a dot followed by one of these
     */
    public function __construct(
        public array $dnsSuffixes = [],
    ) {
    }
}
