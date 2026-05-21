<?php

namespace CleverCloud\Sdk\Tests\Unit\Model\Enum;

use CleverCloud\Sdk\Model\Enum\ApplicationState;
use CleverCloud\Sdk\Model\Enum\DeployType;
use CleverCloud\Sdk\Model\Enum\Flavor;
use CleverCloud\Sdk\Model\Enum\MigrationStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The public enums are part of the SDK's contract — they're meant to be
 * imported by user code (form dropdowns, type-safe branching). Lock down
 * their case values so accidental renames don't sneak through.
 */
#[CoversClass(Flavor::class)]
#[CoversClass(DeployType::class)]
#[CoversClass(ApplicationState::class)]
#[CoversClass(MigrationStatus::class)]
final class EnumExposureTest extends TestCase
{
    public function testFlavorCoversPlatformTiers(): void
    {
        $values = array_map(static fn (Flavor $f): string => $f->value, Flavor::cases());

        self::assertSame(['pico', 'nano', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'], $values);
    }

    public function testDeployTypeCases(): void
    {
        $values = array_map(static fn (DeployType $d): string => $d->value, DeployType::cases());

        self::assertSame(['git', 'ftp', 'docker'], $values);
    }

    public function testApplicationStateParsesApiCodes(): void
    {
        self::assertSame(ApplicationState::ShouldBeUp, ApplicationState::from('SHOULD_BE_UP'));
        self::assertSame(ApplicationState::WantsToBeUp, ApplicationState::from('WANTS_TO_BE_UP'));
        self::assertNull(ApplicationState::tryFrom('UNKNOWN_STATE'));
    }

    public function testApplicationStateStabilityHelpers(): void
    {
        self::assertTrue(ApplicationState::ShouldBeUp->isStable());
        self::assertTrue(ApplicationState::ShouldBeDown->isStable());
        self::assertFalse(ApplicationState::ShouldBeUp->isTransient());
        self::assertTrue(ApplicationState::Deploying->isTransient());
        self::assertTrue(ApplicationState::WantsToBeUp->isTransient());
        self::assertFalse(ApplicationState::RestartFailed->isTransient());
    }

    public function testMigrationStatusTerminalCases(): void
    {
        self::assertTrue(MigrationStatus::Success->isTerminal());
        self::assertTrue(MigrationStatus::Failed->isTerminal());
        self::assertTrue(MigrationStatus::Cancelled->isTerminal());
        self::assertFalse(MigrationStatus::Pending->isTerminal());
        self::assertFalse(MigrationStatus::InProgress->isTerminal());
    }
}
