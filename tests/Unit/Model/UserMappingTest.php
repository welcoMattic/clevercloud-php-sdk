<?php

namespace CleverCloud\Sdk\Tests\Unit\Model;

use AutoMapper\AutoMapper;
use CleverCloud\Sdk\Model\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserMappingTest extends TestCase
{
    /**
     * The keys below are the ones `/v2/self` actually returns, verified against
     * the live API.
     *
     * A previous version of this test fed snake_case keys (`has_password`,
     * `creation_date`, ...) that lined up with the model's `MapFrom`
     * attributes. It passed, while every real response left those properties
     * null, because the API speaks camelCase. Keep the fixture aligned with the
     * wire format, not with the model.
     */
    public function testMapsFullPayloadIntoUserDto(): void
    {
        $mapper = AutoMapper::create();

        /** @var User|null $user */
        $user = $mapper->map([
            'id' => 'user_abc',
            'email' => 'alice@example.com',
            'name' => 'Alice Smith',
            'phone' => '+33000000000',
            'address' => '1 rue de la Paix',
            'city' => 'Paris',
            'zipcode' => '75002',
            'country' => 'FR',
            'avatar' => 'https://example.com/a.png',
            'lang' => 'en',
            'preferredMFA' => 'TOTP',
            'hasPassword' => true,
            'canPay' => false,
            'emailValidated' => true,
            'creationDate' => 1_700_000_000_000,
        ], User::class);

        self::assertNotNull($user);
        self::assertSame('user_abc', $user->id);
        self::assertSame('alice@example.com', $user->email);
        self::assertSame('Alice Smith', $user->name);
        self::assertSame('+33000000000', $user->phone);
        self::assertSame('FR', $user->country);
        self::assertSame('en', $user->lang);
        self::assertSame('TOTP', $user->preferredMfa);
        self::assertTrue($user->hasPassword);
        self::assertFalse($user->canPay);
        self::assertTrue($user->emailValidated);
        self::assertSame(1_700_000_000_000, $user->creationDate);
    }

    public function testMapsMinimalPayloadWithNullDefaults(): void
    {
        $mapper = AutoMapper::create();

        /** @var User|null $user */
        $user = $mapper->map(['id' => 'user_min'], User::class);

        self::assertNotNull($user);
        self::assertSame('user_min', $user->id);
        self::assertNull($user->email);
        self::assertNull($user->name);
        self::assertNull($user->hasPassword);
        self::assertNull($user->creationDate);
    }
}
