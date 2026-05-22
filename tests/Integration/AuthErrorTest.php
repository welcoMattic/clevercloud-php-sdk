<?php

namespace CleverCloud\Sdk\Tests\Integration;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\AuthException;

/**
 * Verifies the SDK's error mapping for the auth/transport layer: a request
 * made with a deliberately invalid token must raise AuthException, not a
 * generic ApiException.
 *
 * This test does NOT require the env vars used by the rest of the suite —
 * it constructs its own bad credentials and only checks the gateway's
 * response handling. It's still in the `integration` group because it hits
 * the real Clever Cloud API.
 */
final class AuthErrorTest extends IntegrationTestCase
{
    public function testInvalidBearerTokenRaisesAuthException(): void
    {
        $client = new ClientBuilder()
            ->withCredentials(Credentials::apiToken('cc_definitely_not_a_real_token_zzz'))
            ->build();

        $this->expectException(AuthException::class);

        $client->self->get();
    }
}
