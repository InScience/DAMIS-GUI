<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ExperimentController extends Controller
{
    /**
     * New experiment workflow creation window
     *
     * @Route("/experiment/new.html", name="new_experiment")
     * @Template()
     */
    public function newAction()
    {
        $clusters = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Cluster')
            ->findAll();

        $componentsCategories = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:ComponentType')
            ->findAll();

        $components = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Component')
            ->findAll();


        return [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
        ];
    }

    /**
     * Edit experiment in workflow creation window
     *
     * @Route("/experiment/{id}/edit.html", name="edit_experiment")
     * @Template()
     */
    public function editAction($id)
    {
        return array();
    }

    /**
     * Component info
     *
     * @Route("/experiment/component/{id}/info.html", name="component", options={"expose" = true})
     * @Template()
     */
    public function componentAction($id)
    {
        $parameters = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Parameter')
            ->findBy(['component' => $id]);

        return [
            'parameters' => $parameters
        ];
    }

    /**
     * Experiment save
     *
     * @Route("/experiment/save.html", name="experiment_save")
     * @Template()
     */
    public function saveAction()
    {

        return [];
    }

    /**
     * Experiment execution
     *
     * @Route("/experiment/execute.html", name="experiment_execute")
     * @Template()
     */
    public function executeAction()
    {

        return [];
    }

}
