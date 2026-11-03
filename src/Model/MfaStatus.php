<?php

namespace CleverCloud\Sdk\Model;

/**
 * Whether a multi-factor authentication kind (such as `TOTP`) is configured
 * for the current user, as returned by `/v2/self/mfa/{kind}`.
 *
 * `backupCodesState` stays a raw array (`{remaining, total, generatedAt}`) and
 * is null when `configured` is false.
 */
final readonly class MfaStatus
{
    /**
     * @param array<string, mixed>|null $backupCodesState
     */
    public function __construct(
        public string $kind,
        public bool $configured = false,
        public ?array $backupCodesState = null,
    ) {
    }
}
