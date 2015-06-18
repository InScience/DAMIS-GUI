<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ExperimentHistoryController extends Controller
{

    /**
     * Lists all User entities.
     *
     * @Route("experiments.html", name="experiments_history")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        // checks MIDAS session
        $this->get("midas_service")->checkSession();
        
        $em = $this->getDoctrine()->getManager();
        $userId = $this->get('security.context')->getToken()->getUser()->getId();
        $entities = $em->getRepository('DamisExperimentBundle:Experiment')->getUserExperiments($userId);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1),
            15
        );

        return array(
            'entities' => $pagination
        );
    }

    /**
     * Lists all User entities.
     *
     * @Route("experiments_examples.html", name="experiments_examples")
     * @Method("GET")
     * @Template()
     */
    public function examplesAction()
    {
        // checks MIDAS session
        $this->get("midas_service")->checkSession();
        
        $em = $this->getDoctrine()->getManager();
        $userId = $this->get('security.context')->getToken()->getUser()->getId();
        $entities = $em->getRepository('DamisExperimentBundle:Experiment')->getExperimentsExamples();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1),
            15
        );

        return array(
            'entities' => $pagination
        );
    }
}
