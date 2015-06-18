<?php

namespace Damis\ExperimentBundle\Controller;

use Base\ConvertBundle\Helpers\ReadFile;
use Damis\DatasetsBundle\Entity\Dataset;
use Guzzle\Http\Client;
use PHPExcel_IOFactory;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Damis\EntitiesBundle\Entity\Parametervalue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Damis\EntitiesBundle\Entity\Pvalueoutpvaluein;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

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
        // checks MIDAS session
        $this->get("midas_service")->checkSession();
                
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

        /* @var $experimentRepository \Damis\ExperimentBundle\Entity\ExperimentRepository */
        $experimentRepository = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment');

        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        $nextName = $experimentRepository->getNextExperimentNameNumber($user->getId());

        return [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
            'workFlowState' => null,
            'taskBoxesCount' => 0,
            'experimentId' => null,
            'experimentTitle' => 'exp'.$nextName
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
        // checks MIDAS session
        $this->get("midas_service")->checkSession();

        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();

        /* @var $experiment Experiment */
        $experiment = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneById($id);
        
        // Validation of user access to current experiment
        if (!$experiment || $experiment->getUser() != $user) {
            $this->container->get('logger')->addError('Unvalid try to access experiment by user id: '.$user->getId());
            return $this->redirectToRoute('experiments_history');
        }
        
        $data = $this->newAction();
        
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
        // checks MIDAS session
        $this->get("midas_service")->checkSession();

        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        $params = $request->request->all();
        $isValid = isset($params['valid_form']);
        if ($isValid) {
            $isValid = $params['valid_form'] == 1 ? true : false;
        }
        $isChanged = isset($params['workflow_changed']);
        if ($isChanged) {
            $isChanged = $params['workflow_changed'] == 1 ? true : false;
        }

        /* @var $experiment Experiment */
        if ($params['experiment-id']) {
            $experiment = $this->getDoctrine()
                ->getRepository('DamisExperimentBundle:Experiment')
                ->findOneBy(['id' => $params['experiment-id'], 'user' => $user]);
        } else {
            $experiment = false;
        }

        $isNew = !(boolean) $experiment;
        if ($isNew) {
            $experiment = new Experiment();
        }

        $experiment->setName($params['experiment-title']);
        $experiment->setGuiData($params['experiment-workflow_state']);
        $experiment->setFinish(null);
        $isExecution = isset($params['experiment-execute']);
        $stopTask = isset($params['experiment-execute-task-box']) ? $params['experiment-execute-task-box'] : 0;

        if ($isExecution) {
            $isExecution = ($params['experiment-execute'] > 0);
        }

        if ($isExecution) {
            $experiment->setMaxDuration(new \DateTime($params['experiment-max_calc_time']));
            $experiment->setUseCpu($params['experiment-p']);
            $experiment->setUsePrimaryMemory($params['experiment-ram']);
            $experiment->setUseSecMemory($params['experiment-hdd']);
            $experiment->setStart(strtotime($params['experiment-start']));
        }

        $experiment->setUser($this->get('security.context')->getToken()->getUser());

        $em = $this->getDoctrine()->getManager();
        
        $experimentStatusSaved = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'SAVED']);
               
        if ($isValid) {
            $this->get('session')->getFlashBag()->add('success', 'Experiment successfully created!');
            // If if something is changed we change status
            if ($isExecution || $isChanged || $isNew) {
                $experiment->setStatus($experimentStatusSaved);
            } else {
                if (!$isExecution) {
                    $this->get('session')->getFlashBag()->add('success', 'Experiment status is not changed!');
                }
            }
        }
        
        $em->persist($experiment);
        $em->flush();
        
        if ($isExecution && $isValid) {
            $this->populate($experiment->getId(), $stopTask);
            $this->get('session')->getFlashBag()->add('success', 'Experiment is started');
        }

        if ($isValid) {
            return ['experiment' => $experiment];
        } else {
            return new Response($experiment->getId());
        }
    }

    /**
     * Experiment execution
     *
     * @Route("/experiment/{id}/execute.html", name="execute_experiment")
     * @Template()
     */
    public function executeAction($id)
    {
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
        $experiment = $em
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        $this->populate($id, 0);

        return $this->redirect($this->get('request')->headers->get('referer'));
    }

    /**
     * This action will create all experiment task id database that are required
     * for execution.
     *
     * @param integer $id       Experiment id
     * @param string  $stopTask Task from wih other task will be not executed
     * @throws type
     */
    public function populate($id, $stopTask)
    {
        $em = $this->getDoctrine()->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
        $experiment = $em
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        // Seting experiment status to Executing
        $experimentStatusExecuting = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'EXECUTING']);
        $experiment->setStatus($experimentStatusExecuting);
                
        $guiDataExploded = explode('***', $experiment->getGuiData());
        $workflows = json_decode($guiDataExploded[0]);
        $workflowsConnections = json_decode($guiDataExploded[1]);
      
        //remove workflotasks at first, this should remove parametervalues and parametervaluein-out too
        foreach ($experiment->getWorkflowtasks() as $task) {
            $em->remove($task);
        }
        $em->flush();

        $workflowsSaved = array();

        foreach ($workflows as $workflow) {
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
            foreach ($component->getParameters() as $parameter) {
                $value = new Parametervalue();
                $value->setWorkflowtask($workflowTask);
                $value->setParameter($parameter);
                $value->setParametervalue(null);

                foreach ($workflow->form_parameters as $form) {
                    if ($form) {
                        if (!isset($form->id) or !isset($form->value)) {
                            continue;
                        }
                        if ($form->id == $parameter->getId()) {
                            if (is_array($form->value)) {
                                $value->setParametervalue(json_encode($form->value));
                            }
                        } else {
                            $value->setParametervalue($form->value);
                        }
                    }
                }
                $em->persist($value);
                $em->flush();

                if ($parameter->getConnectionType()->getId() == '1') {
                    $wf['in'] = $value->getParametervalueid();
                }
                if ($parameter->getConnectionType()->getId() == '2') {
                    $wf['out'][$parameter->getSlug()] = $value->getParametervalueid();
                }

            }

            $wf['id'] = $workflowTask->getWorkflowtaskid();
            $workflowsSaved[$workflow->boxId] = $wf;
        }

        foreach ($workflowsConnections as $conn) {
            if (isset($workflowsSaved[$conn->sourceBoxId]) and isset($workflowsSaved[$conn->targetBoxId])) {
                if ((isset($workflowsSaved[$conn->sourceBoxId]['out']['Y']) or isset($workflowsSaved[$conn->sourceBoxId]['out']['Yalt'])) and isset($workflowsSaved[$conn->targetBoxId]['in'])) {
                    //sugalvojom tokia logika:
                    //jei nustaytas sourceAnchor tipas vadinasi tai yra Y connectionas
                    //by default type = Right
                    if (isset($conn->sourceAnchor->type) and ($conn->sourceAnchor->type == "Right")) {
                        /* @var $valOut \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valOut = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->sourceBoxId]['out']['Y'] ]);

                        /* @var $valIn \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valIn = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->targetBoxId]['in'] ]);
                    } else {
                        /* @var $valOut \Damis\EntitiesBundle\Entity\Parametervalue */
                        $valOut = $em
                            ->getRepository('DamisEntitiesBundle:Parametervalue')
                            ->findOneBy(['parametervalueid' => $workflowsSaved[$conn->sourceBoxId]['out']['Yalt'] ]);

                        /* @var $valIn \Damis\EntitiesBundle\Entity\Parametervalue */
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
        
        /// Remove not runable tasks whet stop task is isset
        if ($stopTask) {
            $tasksToRemove = array();
            foreach ($workflowsConnections as $conn) {
                if ($conn->sourceBoxId === $stopTask || in_array($conn->sourceBoxId, $tasksToRemove)) {
                    $tasksToRemove[] = $conn->targetBoxId ;
                }
            }

            /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
            $experiment = $em
                ->getRepository('DamisExperimentBundle:Experiment')
                ->findOneBy(['id' => $id]);
            $em->refresh($experiment);

            foreach ($experiment->getWorkflowtasks() as $task) {
                if (in_array($task->getTaskBox(), $tasksToRemove)) {
                    $em->remove($task);
                }
            }
            $em->flush();
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
        // checks MIDAS session
        $this->get("midas_service")->checkSession();

        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();

        /* @var $experiment Experiment */
        $experiment = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Experiment')
            ->findOneById($id);

        // Validation of user access to current experiment
        if (!$experiment || $experiment->getUser() != $user) {
            $this->container->get('logger')->addError('Unvalid try to access experiment by user id: '.$user->getId());
            return $this->redirectToRoute('experiments_history');
        }
        
        $data = $this->newAction();

        $tasksBoxsWithErrors = [];
        $executedTasksBoxs = [];
        /* @var $task Workflowtask */
        foreach ($experiment->getWorkflowtasks() as $task) {
            /* @var $value \Damis\EntitiesBundle\Entity\Parametervalue */
            foreach ($task->getParameterValues() as $value) {
                if ($value->getParameter()->getConnectionType()->getId() == 2) {
                    $data['datasets'][$task->getTaskBox()][] = $value->getParametervalue();
                }
            }

            if (in_array($task->getWorkflowtaskisrunning(), [1, 3])) {
                $tasksBoxsWithErrors[] = $task->getTaskBox();
            } elseif ($task->getWorkflowtaskisrunning() == 2)
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
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        $experiments = json_decode($request->request->get('experiment-delete-list'));
        $em = $this->getDoctrine()->getManager();
        foreach ($experiments as $id) {
            /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment */
            $experiment = $em->getRepository('DamisExperimentBundle:Experiment')->findOneById($id);
            if ($experiment && ($user == $experiment->getUser())) {
                $files = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getExperimentDatasets($id);
                foreach ($files as $fileId) {
                    /* @var $file \Damis\DatasetsBundle\Entity\Dataset */
                    $file = $em->getRepository('DamisDatasetsBundle:Dataset')
                        ->findOneBy(array('datasetId' => $fileId, 'hidden' => true));
                    if ($file) {
                        if (file_exists('.'.$file->getFilePath())) {
                            unlink('.'.$file->getFilePath());
                        }
                        $em->remove($file);
                    }
                }
                $em->remove($experiment);
                $em->flush();
            }
        }
        return $this->redirect($this->generateUrl('experiments_history'));
    }

    /**
     * Experiment example copy to user experiments history
     *
     * @Route("/experiment/copy.html", name="experiment_copy")
     * @Method({"GET"})
     */
    public function copyAction(Request $request)
    {
        // checks MIDAS session
        $this->get("midas_service")->checkSession();

        // Session user
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        /* var \Symfony\Component\HttpFoundation\Request */
        $experimentId = intval($request->get('experiment-example-id'));

        $em = $this->getDoctrine()->getManager();
        
        $experimentStatusExample = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'EXAMPLE']);
                
        /* @var $experiment Experiment */
        if ($experimentId > 0) {
            $experiment = $this->getDoctrine()
                ->getRepository('DamisExperimentBundle:Experiment')
                ->findOneBy(['id' => $experimentId, 'status' => $experimentStatusExample]);
        } else {
            return $this->redirectToRoute('experiments_examples');
        }
        
        // Validation
        if (!$experiment) {
            $this->container->get('logger')->addError('Unvalid try to copy experiment by user id: '.$user->getId());
            return $this->redirectToRoute('experiments_history');
        }
      
        // If experiment id is not valid or not example
        if (!$experiment || $experiment->getStatus()->getExperimentstatus() != 'EXAMPLE') {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        /* @var $newExperiment Experiment */
        $newExperiment = new Experiment();
        $newExperiment->setName($experiment->getName());
        $newExperiment->setUser($user);
        $newExperiment->setUseCpu($experiment->getUseCpu());
        $newExperiment->setUsePrimaryMemory($experiment->getUsePrimaryMemory());
        $newExperiment->setUseSecMemory($experiment->getUseSecMemory());
             
        // Set experiment status to SAVED
        $newExperiment->setStatus($em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(1));
        
        $newExperiment->setGuiData($experiment->getGuiData());
        
        $em->persist($newExperiment);
        $em->flush();
        
        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Experiment was copied', array(), 'ExperimentBundle'));
        return $this->redirect($this->generateUrl('experiments_history'));
    }

    /**
     * Experiment example copy to user experiments history
     *
     * @Route("/experiment/example_copy.html", name="experiment_example_copy")
     * @Method({"GET"})
     */
    public function exampleCopyAction(Request $request)
    {
        // checks MIDAS session
        $this->get("midas_service")->checkSession();
        
        // Session user
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        if (!$user->hasRole('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        
        /* var \Symfony\Component\HttpFoundation\Request */
        $experimentId = intval($request->get('experiment-example-id'));

        /* @var $experiment Experiment */
        if ($experimentId > 0) {
            $experiment = $this->getDoctrine()
                ->getRepository('DamisExperimentBundle:Experiment')
                ->findOneBy(['id' => $experimentId, 'user' => $user]);
        } else {
            return $this->redirect($this->generateUrl('experiments_examples'));
        }
      
        // If experiment id is not valid or not example
        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }
        
        // Session user
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        /* @var $newExperiment Experiment */
        $newExperiment = new Experiment();
        $newExperiment->setName($experiment->getName());
        $newExperiment->setUser($user);
        $newExperiment->setUseCpu($experiment->getUseCpu());
        $newExperiment->setUsePrimaryMemory($experiment->getUsePrimaryMemory());
        $newExperiment->setUseSecMemory($experiment->getUseSecMemory());
        
        $em = $this->getDoctrine()->getManager();
        
        // Set status to EXAMPLE
        $newExperiment->setStatus($em->getRepository('DamisExperimentBundle:Experimentstatus')
                ->findOneByExperimentstatusid(6));
        
        $newExperiment->setGuiData($experiment->getGuiData());
        
        $em->persist($newExperiment);
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Experiment was copied to experiment examples', array(), 'ExperimentBundle'));
        return $this->redirect($this->generateUrl('experiments_history'));
    }

    /**
     * When uploading csv/txt/tab/xls/xlsx types to arff
     * convert it and save
     *
     * @param String $id
     * @return boolean
     */
    public function uploadArff($id)
    {
        $memoryLimit = ini_get('memory_limit');
        $suffix = '';
        sscanf($memoryLimit, '%u%c', $number, $suffix);
        if (isset($suffix)) {
            $number = $number * pow(1024, strpos(' KMG', $suffix));
        }
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('user' => $user, 'datasetId' => $id));
        if ($entity) {
            $format = explode('.', $entity->getFile()['fileName']);
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'zip') {
                $zip = new ZipArchive();
                $res = $zip->open('./assets'.$entity->getFile()['fileName']);
                $name = $zip->getNameIndex(0);
                if ($zip->numFiles > 1) {
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', array(), 'DatasetsBundle'));
                    return false;
                }

                if ($res === true) {
                    $path = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '/'));
                    $zip->extractTo('.'.$path, $name);
                    $zip->close();
                    $format = explode('.', $name);
                    $format = $format[count($format)-1];
                    $fileReader = new ReadFile();
                    if ($format == 'arff') {
                        $dir = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '.'));
                        $entity->setFilePath($dir.'.arff');
                        $rows = $fileReader->getRows('.'.$entity->getFilePath(), $format);
                        if ($rows === false) {
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                            $em->remove($entity);
                            $em->flush();
                            unlink('.'.$path.'/'.$name);
                            return false;
                        }
                        unset($rows);
                        $em->persist($entity);
                        $em->flush();
                        rename('.'.$path.'/'.$name, '.'.$dir.'.arff');
                        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
                        return true;
                    } elseif ($format == 'txt' || $format == 'tab' || $format == 'csv') {
                        $rows = $fileReader->getRows('.'.$path.'/'.$name, $format);
                        if ($rows === false) {
                            $em->remove($entity);
                            $em->flush();
                            unlink('.'.$path.'/'.$name);
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', array(), 'DatasetsBundle'));
                            return false;
                        }
                        unlink('.'.$path.'/'.$name);
                    } elseif ($format == 'xls' || $format == 'xlsx') {
                        $objPHPExcel = PHPExcel_IOFactory::load('.'.$path.'/'.$name);
                        $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                        array_unshift($rows, null);
                        unlink('.'.$path.'/'.$name);
                        unset($rows[0]);
                    } else {
                        $em->remove($entity);
                        $em->flush();
                        unlink('.'.$path.'/'.$name);
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', array(), 'DatasetsBundle'));
                        return false;
                    }
                }
            } elseif ($format == 'arff') {
                $entity->setFilePath($entity->getFile()['path']);
                if (memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number) {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    return false;
                }
                unset($rows);
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.'.$entity->getFilePath(), $format);
                if ($rows === false) {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    unlink('.'.$entity->getFile()['fileName']);
                    return false;
                }
                unset($rows);
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
                return true;
            } elseif ($format == 'txt' || $format == 'tab' || $format == 'csv') {
                $fileReader = new ReadFile();
                if (memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number) {
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', array(), 'DatasetsBundle'));
                    return false;
                }
                $rows = $fileReader->getRows('./assets'.$entity->getFile()['fileName'], $format);
            } elseif ($format == 'xls' || $format == 'xlsx') {
                $objPHPExcel = PHPExcel_IOFactory::load('./assets'.$entity->getFile()['fileName']);
                $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                array_unshift($rows, null);
                unset($rows[0]);
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return false;
            }
            $hasHeaders = false;
            if (!empty($rows)) {
                foreach ($rows[1] as $header) {
                    if (!(is_numeric($header))) {
                        $hasHeaders = true;
                    }
                }
            }
            $arff = '';
            $arff .= '@relation '.$filename.PHP_EOL;
            if ($hasHeaders) {
                foreach ($rows[1] as $key => $header) {
                    // Remove spaces in header, to fit arff format
                    $header = preg_replace('/\s+/', '_', $header);

                    // Check string is numeric or normal string
                    if (is_numeric($rows[2][$key])) {
                        if (is_int($rows[2][$key] + 0)) {
                            $arff .= '@attribute '.$header.' '.'integer'.PHP_EOL;
                        } elseif (is_float($rows[2][$key] + 0)) {
                            $arff .= '@attribute '.$header.' '.'real'.PHP_EOL;
                        }
                    } else {
                        $arff .= '@attribute '.$header.' '.'string'.PHP_EOL;
                    }
                }
            } else {
                foreach ($rows[1] as $key => $header) {
                    if (is_numeric($rows[2][$key])) {
                        if (is_int($rows[2][$key] + 0)) {
                            $arff .= '@attribute '.'attr'.$key.' '.'integer'.PHP_EOL;
                        } elseif (is_float($rows[2][$key] + 0)) {
                            $arff .= '@attribute '.'attr'.$key.' '.'real'.PHP_EOL;
                        }
                    } else {
                        $arff .= '@attribute '.'attr'.$key.' '.'string'.PHP_EOL;
                    }
                }
            }
            $arff .= '@data'.PHP_EOL;
            if ($hasHeaders) {
                unset($rows[1]);
            }
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    if ($key > 0) {
                        $arff .= ','.$value;
                    } else {
                        $arff .= $value;
                    }
                } 
                $arff .= PHP_EOL;
            }
            $dir = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '.'));
            $fp = fopen($_SERVER['DOCUMENT_ROOT'].$dir.".arff", "w+");
            fwrite($fp, $arff);
            fclose($fp);
            $entity->setFilePath($dir.".arff");
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
            return true;
        }
        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error!', array(), 'DatasetsBundle'));
        return false;
    }
}
