<?php
namespace App\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;
    private $container;

    public function __construct($defaultLocale = 'es', ContainerInterface $container)
    {
        $this->defaultLocale = $defaultLocale;
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        $globals = $this->container->getParameter('global_params');
        // try to see if the currency has been set as a _currency routing parameter
        /*if ($currency = $request->attributes->get('_currency')) {

            //si esta el currency
            if (array_key_exists($currency, $globals['currencies'])) {
                $request->getSession()->set('_currency', $currency);
            }
        } else if(!$request->getSession()->has('_currency')){
            $request->getSession()->set('_currency', $globals['currency']);
        }*/

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            //si esta el locale
            if (array_key_exists($locale, $globals['locales'])) {
                $request->getSession()->set('_locale', $locale);
            }
        } else {
            if($request->getLocale() != $this->defaultLocale){
                // if no explicit locale has been set on this request, use one from the session
                $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
            }
        }


    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}