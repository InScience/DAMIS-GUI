<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Damis\EntitiesBundle\Entity\Parametervalue;
use /** @noinspection PhpUnusedAliasInspection */
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use /** @noinspection PhpUnusedAliasInspection */
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use /** @noinspection PhpUnusedAliasInspection */
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use /** @noinspection PhpUnusedAliasInspection */
    Symfony\Component\HttpFoundation\RedirectResponse;
use Damis\EntitiesBundle\Entity\Pvalueoutpvaluein;
use Symfony\Component\HttpFoundation\Response;

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

        /** @var $experimentRepository \Damis\ExperimentBundle\Entity\ExperimentRepository */
        $experimentRepository = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment');

        $nextName = $experimentRepository->getNextExperimentNameNumber();


        return [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
            'workFlowState' => null,
            'taskBoxesCount' => 0,
            'experimentId' => null,
            'experimentTitle' => 'exp' . $nextName
        ];
    }

    /**
     * Edit experiment in workflow creation window
     *
     * @Route("/experiment/{id}/edit.html", name="edit_experiment")
     * @Template("DamisExperimentBundle:Experiment:new.html.twig")
     */
    public function editAction($id)
    {
        $data = $this->newAction();

        /** @var $experiment Experiment */
        $experiment = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneById($id);

        $data['workFlowState'] = $experiment->getGuiData();
        $data['taskBoxesCount'] = @explode('***', $data['workFlowState'])[2];
        $data['experimentId'] = $id;
        $data['experimentTitle'] = $experiment->getName();

        return $data;
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
        $isValid = isset($params['valid_form']);
        if($isValid)
            $isValid = $params['valid_form'] == 1 ? true : false;
        $isChanged = isset($params['workflow_changed']);
        if($isChanged)
            $isChanged = $params['workflow_changed'] == 1 ? true : false;

        /* @var $experiment Experiment */
        if($params['experiment-id'])
            $experiment = $this->getDoctrine()
                ->getRepository('DamisExperimentBundle:Experiment')
                ->findOneBy(['id' => $params['experiment-id']]);
        else
            $experiment = false;

        $isNew = !(boolean)$experiment;
        if ($isNew)
            $experiment = new Experiment();

        $experiment->setName($params['experiment-title']);
        $experiment->setGuiData($params['experiment-workflow_state']);
        $isExecution = isset($params['experiment-execute']);

        if($isExecution)
            $isExecution = ($params['experiment-execute'] > 0);

        if($isExecution) {
            $experiment->setMaxDuration(new \DateTime($params['experiment-max_calc_time']));
            $experiment->setUseCpu($params['experiment-p']);
        }

        $experiment->setUser($this->get('security.context')->getToken()->getUser());

        $em = $this->getDoctrine()->getManager();
        $oldStatus = false;
        if(!$isNew)
            $oldStatus = $experiment->getStatus();

        if($isExecution && $isChanged && $isValid && !$isNew)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(2);
        elseif(!$isExecution && $isChanged && $isValid && $isNew)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(1);
        elseif($isExecution && $isChanged && $isValid && $isNew)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(2);
        elseif(!$isExecution && $isChanged && $isValid && !$isNew)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(1);
        elseif($isExecution && !$isChanged && $isValid)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(2);
        elseif(!$isExecution && !$isChanged && $isValid && !$isNew)
            $experimentStatus = $oldStatus;
        elseif($isChanged && !$isValid)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(1);
        elseif(!$isChanged && !$isValid)
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(1);
        else
            $experimentStatus = $em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(2);


        if($experimentStatus)
            $experiment->setStatus($experimentStatus);
        $em->persist($experiment);
        $em->flush();

        if($isExecution)
            $this->populate($experiment->getId());
        if($isValid){
            $this->get('session')->getFlashBag()->add('success', 'Experiment successfully created!');
        }
        if($isValid)
            return ['experiment' => $experiment];
        else
            return new Response($experiment->getId());
    }

    /**
     * Experiment execution
     *
     * @Route("/experiment/{id}/execute.html", name="execute_experiment")
     * @Template()
     */
    public function executeAction($id){
        $em = $this->getDoctrine()->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
        $experiment = $em
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        $this->populate($id);

        $experimentStatus = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'EXECUTING']);

        $experiment->setStatus($experimentStatus);
        $em->flush();

        return $this->redirect($this->get('request')->headers->get('referer'));
    }

    public function populate($id){
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

        //remove workflotasks at first, this should remove parametervalues and parametervaluein-out too
        foreach($experiment->getWorkflowtasks() as $task){
            $em->remove($task);
        }
        $em->flush();

        $workflowsSaved = array();

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
            $workflowTask->setTaskBox($workflow->boxId);
            $em->persist($workflowTask);
            $em->flush();

            $wf = array();

            /* @var $parameter \Damis\ExperimentBundle\Entity\Parameter */
            foreach($component->getParameters() as $parameter){
                $value = new Parametervalue();
                $value->setWorkflowtask($workflowTask);
                $value->setParameter($parameter);
                $value->setParametervalue(null);

                foreach($workflow->form_parameters as $form){
                    if ($form){
                        if (!isset($form->id) or !isset($form->value)){
                            continue;
                        }

                        if ($form->id == $parameter->getId())
                            if(is_array($form->value))
                                $value->setParametervalue(json_encode($form->value));
                            else
                                $value->setParametervalue($form->value);
                    }
                }
                $em->persist($value);
                $em->flush();

                if ($parameter->getConnectionType()->getId() == '1'){
                    $wf['in'] = $value->getParametervalueid();
                }
                if ($parameter->getConnectionType()->getId() == '2'){
                    $wf['out'][$parameter->getSlug()] = $value->getParametervalueid();
                }

            }

            $wf['id'] = $workflowTask->getWorkflowtaskid();
            $workflowsSaved[$workflow->boxId] = $wf;
        }

        foreach($workflowsConnections as $conn){
            if (isset($workflowsSaved[$conn->sourceBoxId]) and isset($workflowsSaved[$conn->targetBoxId])){
                if ( (isset($workflowsSaved[$conn->sourceBoxId]['out']['Y']) or isset($workflowsSaved[$conn->sourceBoxId]['out']['Yalt'])) and isset($workflowsSaved[$conn->targetBoxId]['in']) ) {
                    //sugalvojom tokia logika:
                    //jei nustaytas sourceAnchor tipas vadinasi tai yra Y connectionas
                    //by default type = Right
                    if (isset($conn->sourceAnchor->type) and ($conn->sourceAnchor->type == "Right")) {
                        /** @var $valOut \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valOut = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->sourceBoxId]['out']['Y'] ]);

                        /** @var $valIn \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valIn = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->targetBoxId]['in'] ]);
                    } else {
                        /** @var $valOut \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valOut = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->sourceBoxId]['out']['Yalt'] ]);

                        /** @var $valIn \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valIn = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->targetBoxId]['in'] ]);
                    }

                    $connection = new Pvalueoutpvaluein;
                    $connection->setOutparametervalue($valOut);
                    $connection->setInparametervalue($valIn);
                    $valIn->setParametervalue($valOut->getParametervalue());
                    $em->persist($connection);
                    $em->flush();
                }
            }
        }
    }

    /**
     * Edit experiment in workflow creation window
     *
     * @Route("/experiment/{id}/show.html", name="see_experiment")
     * @Template("DamisExperimentBundle:Experiment:new.html.twig")
     */
    public function seeAction($id)
    {
        $data = $this->newAction();

        /** @var $experiment Experiment */
        $experiment = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneById($id);

        $tasksBoxsWithErrors = [];
        $executedTasksBoxs = [];
        /** @var $task Workflowtask */
        foreach($experiment->getWorkflowtasks() as $task) {
            /** @var $value \Damis\EntitiesBundle\Entity\Parametervalue */
            foreach($task->getParameterValues() as $value)
                if($value->getParameter()->getConnectionType()->getId() == 2)
                    $data['datasets'][$task->getTaskBox()][] = $value->getParametervalue();

            if(in_array($task->getWorkflowtaskisrunning(), [1, 3]))
                $tasksBoxsWithErrors[] = $task->getTaskBox();
            elseif($task->getWorkflowtaskisrunning() == 2)
                $executedTasksBoxs[] = $task->getTaskBox();
        }

        $data['workFlowState'] = $experiment->getGuiData();
        $data['taskBoxesCount'] = explode('***', $data['workFlowState'])[2];
        $data['experimentId'] = $id;
        $data['experimentTitle'] = $experiment->getName();
        $data['tasksBoxsWithErrors'] = $tasksBoxsWithErrors;
        $data['executedTasksBoxs'] = $executedTasksBoxs;

        return $data;
    }

    /**
     * Delete experiments
     *
     * @Route("/delete.html", name="experiment_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request)
    {
        $experiments = json_decode($request->request->get('experiment-delete-list'));
        $em = $this->getDoctrine()->getManager();
        foreach($experiments as $id){
            $experiment = $em->getRepository('DamisExperimentBundle:Experiment')->findOneById($id);
            if($experiment){
                $files = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getExperimentDatasets($id);
                foreach($files as $fileId){
                    /** @var $file \Damis\DatasetsBundle\Entity\Dataset */
                    $file = $em->getRepository('DamisDatasetsBundle:Dataset')
                        ->findOneBy(array('datasetId' => $fileId, 'hidden' => true));
                    if($file){
                        if(file_exists('.' . $file->getFilePath()))
                            unlink('.' . $file->getFilePath());
                        $em->remove($file);
                    }
                }
                $em->remove($experiment);
                $em->flush();
            }
        }
        return $this->redirect($this->generateUrl('experiments_history'));
    }
}
