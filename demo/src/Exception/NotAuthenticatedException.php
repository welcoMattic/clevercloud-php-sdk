<?php

namespace App\Exception;

/**
 * Raised when a controller needs the Clever Cloud client but the user hasn't
 * gone through the OAuth login yet. Caught by {@see \App\EventListener\NotAuthenticatedListener}
 * which redirects to /login.
 */
final class NotAuthenticatedException extends \RuntimeException
{
}
