<?php

namespace CleverCloud\Sdk\Model;

/**
 * One recipient of an e-mail notification. `type` is `email` (the address
 * is in `target`), `userid` (the user id is in `target`) or `organisation`
 * (every member of the owner, no `target`).
 */
final readonly class EmailNotificationTarget
{
    public function __construct(
        public string $type,
        public ?string $target = null,
    ) {
    }
}
