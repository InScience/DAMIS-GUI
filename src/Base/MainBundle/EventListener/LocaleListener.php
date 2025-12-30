<?php
namespace Base\MainBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct($defaultLocale = 'lt')
    {
        $this->defaultLocale = 'lt';
    }

    public function onKernelRequest(RequestEvent  $event)
    {
        $request = $event->getRequest();
        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->getSession()->get('_locale')) {
            if ($locale == 'lt_LT') {
                $locale = $this->defaultLocale;
            }
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
            $request->getSession()->set('_locale', $this->defaultLocale);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 17]],
        ];
    }
}
