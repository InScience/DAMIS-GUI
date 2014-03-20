<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\ExperimentBundle\Entity\Component;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Damis\EntitiesBundle\Entity\Parametervalue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
     * Experiment save
     *
     * @Route("/experiment/save.html", name="experiment_save")
     * @Method("POST")
     * @Template()
     */
    public function saveAction(Request $request)
    {
        $params = $request->request->all();

        $experiment = new Experiment;
        $experiment->setName($params['experiment-title']);
        $experiment->setGuiData($params['experiment-workflow_state']);
        $experiment->setUser($this->get('security.context')->getToken()->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($experiment);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Experiment successfully created!');

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

    /**
     * Experiment execution
     *
     * @Route("/experiment/{id}/populate.html", name="populate_experiment")
     * @Template()
     */
    public function populateAction($id){
        $em = $this->getDoctrine()->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
        $experiment = $em
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        $guiDataExploded = explode('***', $experiment->getGuiData());
        $workflows = json_decode($guiDataExploded[0]);
        $workflowsConnections = json_decode($guiDataExploded[1]);
        $workflowCount = $guiDataExploded[2];

        //remove workflotasks at first, this should remove parametervalues and parametervaluein-out too
        foreach($experiment->getWorkflowtasks() as $task){
            $em->remove($task);
        }
        $em->flush();

        foreach($workflows as $workflow){
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em
                ->getRepository('DamisExperimentBundle:Component')
                ->findOneBy(['id' => $workflow->componentId]);

            if (!$component) {
                continue;
            }

            //New workflowtask
            $workflowTask = new Workflowtask();
            $workflowTask->setExperiment($experiment);
            $workflowTask->setWorkflowtaskisrunning(false);
            $em->persist($workflowTask);

            foreach($workflow->form_parameters as $form){
                if ($form){
                    if (!isset($form->id) or !isset($form->value)){
                        continue;
                    }

                    /* @var $component \Damis\ExperimentBundle\Entity\Parameter */
                    $parameter = $em
                        ->getRepository('DamisExperimentBundle:Parameter')
                        ->findOneBy(['id' => $form->id]);

                    if(!$parameter) {
                        continue;
                    }

                    $value = new Parametervalue();
                    $value->setWorkflowtask($workflowTask);
                    $value->setParameter($parameter);
                    $value->setParametervalue($form->value);
                    $em->persist($value);
                }
            }

            $em->flush();
        }

        var_dump($workflows);

        foreach($workflowsConnections as $conn){
            var_dump($conn);
        }

        return $this->redirect($this->get('request')->headers->get('referer'));
    }

}
