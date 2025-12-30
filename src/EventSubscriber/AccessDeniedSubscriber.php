<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class AccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private TokenStorageInterface $tokenStorage
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        // Only show custom access denied for logged-in users
        // Logged-out users will be redirected to login by Symfony
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() || !is_object($token->getUser())) {
            return;
        }

        $response = new Response(
            $this->twig->render('security/access_denied.html.twig'),
            Response::HTTP_FORBIDDEN
        );

        $event->setResponse($response);
    }
}
