<?php

namespace CleverCloud\Sdk\Tests\Unit\Model;

use AutoMapper\AutoMapper;
use CleverCloud\Sdk\Model\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserMappingTest extends TestCase
{
    public function testMapsFullPayloadIntoUserDto(): void
    {
        $mapper = AutoMapper::create();

        /** @var User|null $user */
        $user = $mapper->map([
            'id' => 'user_abc',
            'email' => 'alice@example.com',
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'name' => 'Alice Smith',
            'phone' => '+33000000000',
            'address' => '1 rue de la Paix',
            'city' => 'Paris',
            'zipcode' => '75002',
            'country' => 'FR',
            'avatar' => 'https://example.com/a.png',
            'lang' => 'en',
            'preferred_mfa' => 'TOTP',
            'has_password' => true,
            'can_pay' => false,
            'email_validated' => true,
            'creation_date' => 1_700_000_000_000,
        ], User::class);

        self::assertNotNull($user);
        self::assertSame('user_abc', $user->id);
        self::assertSame('alice@example.com', $user->email);
        self::assertSame('Alice', $user->firstname);
        self::assertSame('Smith', $user->lastname);
        self::assertSame('Alice Smith', $user->name);
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
        self::assertNull($user->hasPassword);
        self::assertNull($user->creationDate);
    }
}
