<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * The lifecycle state of a Clever Cloud application.
 *
 * Returned by the API as a string on the `state` field; this enum lets
 * application code branch on it without hardcoding string literals.
 *
 * `tryFrom()` returns `null` on unknown values — useful when Clever Cloud
 * adds new states the SDK hasn't shipped yet.
 */
enum ApplicationState: string
{
    case ShouldBeUp = 'SHOULD_BE_UP';
    case WantsToBeUp = 'WANTS_TO_BE_UP';
    case ShouldBeDown = 'SHOULD_BE_DOWN';
    case WantsToBeDown = 'WANTS_TO_BE_DOWN';
    case Restart = 'RESTART';
    case RestartRequested = 'RESTART_REQUESTED';
    case RestartFailed = 'RESTART_FAILED';
    case Deploying = 'DEPLOYING';
    case DeploymentPending = 'DEPLOYMENT_PENDING';

    /** A state from which the platform won't transition without user action. */
    public function isStable(): bool
    {
        return self::ShouldBeUp === $this || self::ShouldBeDown === $this;
    }

    /** A state currently in flux — a deploy/restart is moving things around. */
    public function isTransient(): bool
    {
        return !$this->isStable() && self::RestartFailed !== $this;
    }
}
