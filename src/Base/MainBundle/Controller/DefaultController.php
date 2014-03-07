<?php

namespace Base\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="base_main_default_index")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/lt.html", name="change_locale_lt")
     * @Method("GET")
     * @Template()
     */
    public function localeLtAction()
    {
        $this->getRequest()->getSession()->set('_locale', 'lt_LT');
        $request = $this->getRequest();
        $locale = $request->getLocale();
        $request->setLocale('lt_LT');
        return $this->redirect($this->get('request')->headers->get('referer'));
    }

    /**
     * @Route("/en.html", name="change_locale_en")
     * @Method("GET")
     * @Template()
     */
    public function localeEnAction(){
        $this->getRequest()->getSession()->set('_locale', 'en_US');
        $request = $this->getRequest();
        $locale = $request->getLocale();
        $request->setLocale('en_US');
        return $this->redirect($this->get('request')->headers->get('referer'));
    }
}
