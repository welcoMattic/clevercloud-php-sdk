<?php

namespace CleverCloud\Sdk\Tests\Integration;

/**
 * Hits /v2/self and its read-only sub-resources.
 *
 * Verifies the most fundamental round-trip the SDK supports: request reaches
 * Clever Cloud, response decodes, AutoMapper hydrates a typed DTO.
 */
final class SelfTest extends IntegrationTestCase
{
    public function testGetReturnsAuthenticatedUser(): void
    {
        $me = $this->client->self->get();

        self::assertNotEmpty($me->id, 'Authenticated user should always have an id.');
        // Email may be null on accounts that only use SSO, but if set it's a valid mailbox.
        if (null !== $me->email) {
            self::assertStringContainsString('@', $me->email);
        }
    }

    public function testListSshKeysReturnsTypedCollection(): void
    {
        $keys = $this->client->self->sshKeys();

        self::assertIsList($keys);
        foreach ($keys as $key) {
            self::assertNotEmpty($key->name);
            self::assertNotEmpty($key->key);
        }
    }

    public function testListEmailAddressesReturnsTypedCollection(): void
    {
        $emails = $this->client->self->emailAddresses();

        self::assertIsList($emails);
        // EmailAddress shape is trivial (email + flags) — the fact that
        // mapping succeeded is the integration guarantee.
    }

    public function testListConsumersReturnsTypedCollection(): void
    {
        $consumers = $this->client->self->consumers();

        self::assertIsList($consumers);
        foreach ($consumers as $consumer) {
            self::assertNotEmpty($consumer->key);
        }
    }
}
