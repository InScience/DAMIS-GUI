<?php

namespace Base\UserBundle\EventSubscriber;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResettingRedirectSubscriber implements EventSubscriberInterface
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'onResettingSuccess',
        ];
    }

    public function onResettingSuccess(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_profile_edit');
        $response = new RedirectResponse($url);
        $event->setResponse($response);
    }
}