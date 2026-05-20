<?php

namespace App\EventListener;

use App\Exception\NotAuthenticatedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Catches {@see NotAuthenticatedException} thrown by the SDK factory when the
 * session has no user token yet, and redirects the visitor to /login.
 */
final readonly class NotAuthenticatedListener
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
    }

    #[AsEventListener]
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotAuthenticatedException) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urls->generate('login')));
    }
}
