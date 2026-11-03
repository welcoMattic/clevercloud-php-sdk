<?php

namespace CleverCloud\Sdk\Model;

/**
 * Response of a service token creation: the metadata plus the token value.
 *
 * `token` is shown in this response only and cannot be read again: store it
 * immediately. Use it with {@see \CleverCloud\Sdk\Auth\Credentials::serviceToken()}.
 */
final readonly class CreatedServiceToken
{
    public function __construct(
        public ServiceToken $metadata,
        public string $token,
        /** Always `null`: a service token cannot be refreshed. */
        public ?string $refreshToken = null,
    ) {
    }
}
