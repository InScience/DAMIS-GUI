<?php

namespace Damis\ExperimentBundle\Controller;

use Damis\ExperimentBundle\Entity\Cluster;
use Damis\ExperimentBundle\Entity\ComponentType;
use Damis\ExperimentBundle\Entity\Component;
use Damis\ExperimentBundle\Entity\Experimentstatus;
use Base\ConvertBundle\Helpers\ReadFile;
use Damis\DatasetsBundle\Controller\MidasController; 
use Damis\DatasetsBundle\Entity\Dataset;
use Damis\EntitiesBundle\Entity\Parametervalue;
use Damis\EntitiesBundle\Entity\Pvalueoutpvaluein;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Damis\ExperimentBundle\Entity\Experiment;
use PHPExcel_IOFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;
use Doctrine\Persistence\ManagerRegistry;

class ExperimentController extends AbstractController
{
    /**
     * Inject services via the constructor
     */
    public function __construct(
        private readonly MidasController $midasService, 
        private readonly LoggerInterface $logger, 
        private readonly TranslatorInterface $translator, 
        private readonly ManagerRegistry $doctrine
    ) {
    }

    /**
     * New experiment workflow creation window
     */
    #[Route("/experiment/new.html", name: "new_experiment")]
    public function new(): Response
    {
        $this->midasService->checkSession();

         $clusters = $this->doctrine
            ->getManager()
            ->getRepository(Cluster::class)
            ->findAll();

        $componentsCategories = $this->doctrine
            ->getManager()
            ->getRepository(ComponentType::class)
            ->findAll();

        $components = $this->doctrine
            ->getManager()
            ->getRepository(Component::class)
            ->findAll();

        $experimentRepository = $this->doctrine
            ->getManager()
            ->getRepository(Experiment::class);

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        $nextName = $experimentRepository->getNextExperimentNameNumber($user);

        return $this->render('@DamisExperiment/Experiment/new.html.twig', [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
            'workFlowState' => null,
            'taskBoxesCount' => 0,
            'experimentId' => null,
            'experimentTitle' => 'exp'.$nextName
        ]);
    }

   /**
     * Edit experiment in workflow creation window
     */
    #[Route("/experiment/{id}/edit.html", name: "edit_experiment")]
    public function edit($id): Response
    {
        $this->midasService->checkSession();

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        /** @var Experiment|null $experiment */
        $experiment = $this->doctrine
            ->getManager()
            ->getRepository(Experiment::class)
            ->find($id);

        if (!$experiment || $experiment->getUser() != $user) {
            $this->logger->error('Invalid try to access experiment by user id: '.$user->getId());
            return $this->redirectToRoute('experiments_history');
        }

        $clusters = $this->doctrine
            ->getManager()
            ->getRepository(Cluster::class)
            ->findAll();

        $componentsCategories = $this->doctrine
            ->getManager()
            ->getRepository(ComponentType::class)
            ->findAll();

        $components = $this->doctrine
            ->getManager()
            ->getRepository(Component::class)
            ->findAll();

        $data = [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
        ];

        $data['workFlowState'] = $experiment->getGuiData();
        $data['taskBoxesCount'] = @explode('***', (string) $data['workFlowState'])[2];
        $data['experimentId'] = $id;
        $data['experimentTitle'] = $experiment->getName();

        return $this->render('@DamisExperiment/Experiment/new.html.twig', $data);
    }

    /**
     * Experiment save
     */
    #[Route("/experiment/save.html", name: "experiment_save", methods: ["POST"])]
    public function save(Request $request)
    {
        $this->midasService->checkSession();

        /* @var $user \Base\UserBundle\Entity\User|null */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        $params = $request->request->all();
        $isValid = isset($params['valid_form']) && $params['valid_form'] == 1;
        $isChanged = isset($params['workflow_changed']) && $params['workflow_changed'] == 1;

        /* @var $experiment Experiment|null */
        $experiment = null;
        if (!empty($params['experiment-id'])) {
            $experiment = $this->doctrine
                ->getRepository(Experiment::class)
                ->findOneBy(['id' => $params['experiment-id'], 'user' => $user]);
        }

        $isNew = !$experiment;
        if ($isNew) {
            $experiment = new Experiment();
        }

        $experiment->setName($params['experiment-title']);
        $experiment->setGuiData($params['experiment-workflow_state']);
        $experiment->setFinish(null);
        
        // Check if user clicked Execute button
        $isExecution = isset($params['experiment-execute']) && $params['experiment-execute'] > 0;
        $stopTask = $params['experiment-execute-task-box'] ?? 0;

        if ($isExecution) {
            try {
                // Only try to parse if not empty
                if (!empty($params['experiment-max_calc_time'])) {
                    $maxDuration = new \DateTime($params['experiment-max_calc_time']);
                    $experiment->setMaxDuration($maxDuration);
                } else {
                    // Default to null or a specific time if required
                    $experiment->setMaxDuration(null); 
                }
            } catch (\Exception $e) {
                 $this->addFlash('error', 'Invalid maximum calculation time format.');
                 $isExecution = false; 
                 $isValid = false; 
            }

            $experiment->setUseCpu($params['experiment-p'] ?? 1); // Default to 1 if missing
            $experiment->setUsePrimaryMemory($params['experiment-ram'] ?? null);
            $experiment->setUseSecMemory($params['experiment-hdd'] ?? null);

            $startTime = $params['experiment-start'] ?? null;

            if (empty($startTime)) {
                // If empty, default to 0 (immediate execution)
                $experiment->setStart(0);
            } elseif (is_numeric($startTime)) {
                // If it is already a timestamp/number
                $experiment->setStart((int)$startTime);
            } else {
                // If it is a Date String, try to convert it
                try {
                    $dateObj = new \DateTime($startTime);
                    $experiment->setStart($dateObj->getTimestamp());
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Invalid start time format.');
                    $isExecution = false;
                    $isValid = false;
                }
            }
        }

        $experiment->setUser($this->getUser());

        $em = $this->doctrine->getManager();

        $experimentStatusSaved = $em
            ->getRepository(Experimentstatus::class)
            ->findOneBy(['experimentstatus' => 'SAVED']);

        if ($isValid) {
            $this->addFlash('success', 'Experiment successfully created!');
            if ($isExecution || $isChanged || $isNew) {
                $experiment->setStatus($experimentStatusSaved);
            } else {
                 $this->addFlash('success', 'Experiment status is not changed!');
            }
        } else if (!$isNew) { 
             $this->addFlash('error', 'Experiment data is invalid.');
        }

        if($isValid) { 
            $em->persist($experiment);
            $em->flush(); 

            if ($isExecution) { 
                $this->populate($experiment->getId(), $stopTask);
                $this->addFlash('success', 'Experiment is started');
            }
            
            if ($request->isXmlHttpRequest()) {
                return new Response($this->generateUrl('edit_experiment', ['id' => $experiment->getId()]));
            }

            return $this->redirectToRoute('edit_experiment', ['id' => $experiment->getId()]);
        } else {
             if ($isNew) {
                 return $this->redirectToRoute('new_experiment'); 
             } else {
                 return $this->redirectToRoute('edit_experiment', ['id' => $experiment->getId()]);
             }
        }
    }

    /**
     * Experiment execution
     */
    #[Route("/experiment/{id}/execute.html", name: "execute_experiment")]
    // Removed #[Template] because this action redirects
    public function execute(Request $request, $id) // Added Request for referer
    {
        /* @var $user \Base\UserBundle\Entity\User|null */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }
        
        $em = $this->doctrine->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment|null */
        $experiment = $em
            ->getRepository(Experiment::class)
            ->findOneBy(['id' => $id, 'user' => $user]); // Ensure user owns experiment

        if (!$experiment) {
            throw $this->createNotFoundException('Unable to find Experiment entity.');
        }

        // Check if experiment can be executed (e.g., status is SAVED)
        if ($experiment->getStatus()->getExperimentstatus() !== 'SAVED') {
             $this->addFlash('warning', 'Experiment cannot be executed in its current state.');
             return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('experiments_history'));
        }


        $this->populate($id, 0);
        $this->addFlash('success', 'Experiment started successfully.'); // Add feedback

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('edit_experiment', ['id' => $id])); // Redirect back or to edit
    }

/**
     * Populates workflow tasks based on experiment data.
     * (Internal method, no route)
     */
    public function populate($id, $stopTask)
    {
        $em = $this->doctrine->getManager();

        /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment|null */
        $experiment = $em
            ->getRepository(Experiment::class)
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
            $this->logger->error("Populate called with invalid experiment ID: {$id}");
            return; 
        }

        $experimentStatusExecuting = $em
            ->getRepository(Experimentstatus::class)
            ->findOneBy(['experimentstatus' => 'EXECUTING']);
        
        if ($experimentStatusExecuting) {
            $experiment->setStatus($experimentStatusExecuting);
        } else {
            $this->logger->error("EXECUTING status not found in database.");
            return; 
        }
              
        $guiData = $experiment->getGuiData();

        if (empty($guiData)) {
            $this->logger->error("Experiment ID {$id} has empty GUI data.");
             $this->addFlash('error', 'Experiment workflow data is missing.');
            return;
        }

        $guiDataExploded = explode('***', $guiData);
        if (count($guiDataExploded) < 2) {
             $this->logger->error("Experiment ID {$id} has invalid GUI data format (explode failed).");
             $this->addFlash('error', 'Experiment workflow data format is invalid.');
             return;
        }

        // Clean strings
        $strWorkflows = trim(html_entity_decode($guiDataExploded[0]));
        $strConnections = trim(html_entity_decode($guiDataExploded[1]));

        // Decode JSON
        $workflows = json_decode($strWorkflows);
        $workflowsConnections = json_decode($strConnections);

        // Convert the top-level Objects to Arrays so we can iterate them.
        if (is_object($workflows)) {
            $workflows = (array)$workflows;
        }
        if (is_object($workflowsConnections)) {
            $workflowsConnections = (array)$workflowsConnections;
        }

        // Handle empty strings resulting in null
        if ($workflows === null && $strWorkflows === "") { $workflows = []; }
        if ($workflowsConnections === null && $strConnections === "") { $workflowsConnections = []; }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($workflows) || !is_array($workflowsConnections)) {
             $this->logger->error("Experiment ID {$id} JSON Decode Error: " . json_last_error_msg());
             $this->addFlash('error', 'Failed to parse experiment workflow data.');
             return;
        }

        // Fetch existing tasks to avoid removing unrelated ones
        $existingTasks = $experiment->getWorkflowtasks(); 
        foreach ($existingTasks as $task) {
            $em->remove($task);
        }
        $em->flush(); 
        $experiment->getWorkflowtasks()->clear(); 

        $workflowsSaved = [];

        foreach ($workflows as $workflow) {
            // Basic validation
            if (!isset($workflow->componentId) || !isset($workflow->boxId) || !isset($workflow->form_parameters)) {
                 $this->logger->warning("Skipping invalid workflow item in experiment ID {$id}");
                 continue; 
            }
            
            // Ensure form_parameters is iterable
            if (is_object($workflow->form_parameters)) {
                $workflow->form_parameters = (array)$workflow->form_parameters;
            }

            /* @var $component \Damis\ExperimentBundle\Entity\Component|null */
            $component = $em->getRepository(Component::class)->find($workflow->componentId);

            if (!$component) {
                 $this->logger->warning("Component ID {$workflow->componentId} not found. Skipping task.");
                 continue;
            }
            
            $workflowTask = new Workflowtask();
            $workflowTask->setExperiment($experiment); // Owning side set here (Critical)
            $workflowTask->setWorkflowtaskisrunning(0); 
            $workflowTask->setTaskBox($workflow->boxId);
            $em->persist($workflowTask);
            
            $wf = ['in' => null, 'out' => [], 'id' => null]; 

            /* @var $parameter \Damis\ExperimentBundle\Entity\Parameter */
            foreach ($component->getParameters() as $parameter) {
                $value = new Parametervalue();
                $value->setWorkflowtask($workflowTask);
                $value->setParameter($parameter);
                $value->setParametervalue(null); 

                foreach ($workflow->form_parameters as $form) {
                    if ($form && isset($form->id) && isset($form->value) && $form->id == $parameter->getId()) {
                         $paramValue = is_array($form->value) ? json_encode($form->value) : $form->value;
                         if (strlen((string)$paramValue) > 4000) { 
                             $this->logger->warning("Parameter value too long.");
                         }
                         $value->setParametervalue($paramValue);
                         break; 
                    }
                }
                $em->persist($value);
                
                // REMOVED: $workflowTask->addParameterValue($value);
                // Reason: Likely missing in Entity, and not required for DB persistence.

                $connTypeId = $parameter->getConnectionType() ? $parameter->getConnectionType()->getId() : null;
                if ($connTypeId == '1') { // Input
                     $wf['in_param_id'] = $parameter->getId(); 
                     $wf['in_value_obj'] = $value; 
                } elseif ($connTypeId == '2') { // Output
                    $wf['out_params'][$parameter->getSlug()] = $parameter->getId();
                    $wf['out_value_objs'][$parameter->getSlug()] = $value;
                }
            }
             $em->flush(); 

             $wf['id'] = $workflowTask->getWorkflowtaskid(); 
             $workflowsSaved[$workflow->boxId] = $wf;
        }
         $em->flush(); 

        foreach ($workflowsConnections as $conn) {
             if (!isset($conn->sourceBoxId) || !isset($conn->targetBoxId)) {
                 continue;
             }
             
            $sourceBoxId = $conn->sourceBoxId;
            $targetBoxId = $conn->targetBoxId;

            if (isset($workflowsSaved[$sourceBoxId]) && isset($workflowsSaved[$targetBoxId])) {
                
                $sourceWf = $workflowsSaved[$sourceBoxId];
                $targetWf = $workflowsSaved[$targetBoxId];

                $outputSlug = (isset($conn->sourceAnchor->type) && $conn->sourceAnchor->type === "RightAlt") ? 'Yalt' : 'Y'; 

                if (isset($targetWf['in_value_obj']) && isset($sourceWf['out_value_objs'][$outputSlug])) {
                    
                    $valOut = $sourceWf['out_value_objs'][$outputSlug];
                    $valIn = $targetWf['in_value_obj'];

                    $connectionLink = new Pvalueoutpvaluein();
                    $connectionLink->setOutparametervalue($valOut);
                    $connectionLink->setInparametervalue($valIn);
                    $em->persist($connectionLink);
                }
            }
        }
        $em->flush();
        
        /// Remove tasks if stopTask is set
        if ($stopTask && isset($workflowsSaved[$stopTask])) { 
            $tasksToRemoveBoxIds = [];
            $queue = [$stopTask]; 
            $visited = [$stopTask];

             while (!empty($queue)) {
                $currentBoxId = array_shift($queue);
                foreach ($workflowsConnections as $conn) {
                    if ($conn->sourceBoxId === $currentBoxId && !in_array($conn->targetBoxId, $visited)) {
                        $tasksToRemoveBoxIds[] = $conn->targetBoxId;
                        $queue[] = $conn->targetBoxId;
                        $visited[] = $conn->targetBoxId;
                    }
                }
            }   

            if (!empty($tasksToRemoveBoxIds)) {
                $tasksToRemoveEntities = $em->getRepository(Workflowtask::class)->findBy([
                    'experiment' => $experiment,
                    'taskBox' => $tasksToRemoveBoxIds
                ]);

                foreach ($tasksToRemoveEntities as $taskToRemove) {
                    $em->remove($taskToRemove); 
                }
                 $em->flush(); 
            }
        }
         $em->flush(); 
    }

    /**
     * Show experiment results/status (uses the 'new' template layout)
     */
    #[Route("/experiment/{id}/show.html", name: "see_experiment")]
    #[Template('@DamisExperiment/Experiment/new.html.twig')]
    public function see($id): Response // Added Response type hint
    {
        $this->midasService->checkSession();

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        /** @var Experiment|null $experiment */
        $experiment = $this->doctrine
            ->getManager()
            ->getRepository(Experiment::class)
            ->findOneBy(['id' => $id]);

        if (!$experiment) {
             throw $this->createNotFoundException("Experiment with ID {$id} not found.");
        }
        if ($experiment->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->logger->warning("User {$user->getId()} attempted to view experiment {$id} owned by {$experiment->getUser()->getId()}");
             throw $this->createAccessDeniedException("You do not have permission to view this experiment.");
        }

        $clusters = $this->doctrine->getManager()->getRepository(Cluster::class)->findAll();
        $componentsCategories = $this->doctrine->getManager()->getRepository(ComponentType::class)->findAll();
        $components = $this->doctrine->getManager()->getRepository(Component::class)->findAll();

        $data = [
            'clusters' => $clusters,
            'componentsCategories' => $componentsCategories,
            'components' => $components,
            'datasets' => [],
            'workFlowState' => $experiment->getGuiData(),
            'taskBoxesCount' => 0,
            'experimentId' => $id,
            'experimentTitle' => $experiment->getName(),
            'tasksBoxsWithErrors' => [],
            'executedTasksBoxs' => [],
            'isReadOnlyView' => true,
        ];

        $guiDataParts = explode('***', (string) $data['workFlowState']);
        if (isset($guiDataParts[2]) && is_numeric($guiDataParts[2])) {
            $data['taskBoxesCount'] = (int)$guiDataParts[2];
        }

        $tasksBoxsWithErrors = [];
        $executedTasksBoxs = [];
        foreach ($experiment->getWorkflowtasks() as $task) {
            foreach ($task->getParameterValues() as $value) {
                 if ($value->getParameter() && $value->getParameter()->getConnectionType() && $value->getParameter()->getConnectionType()->getId() == 2) {
                    $data['datasets'][$task->getTaskBox()][] = $value->getParametervalue();
                }
            }
             $taskStatus = $task->getWorkflowtaskisrunning();
            if ($taskStatus === 1 || $taskStatus === 3) {
                $tasksBoxsWithErrors[] = $task->getTaskBox();
            } elseif ($taskStatus === 2) {
                $executedTasksBoxs[] = $task->getTaskBox();
            }
        }
        $data['tasksBoxsWithErrors'] = $tasksBoxsWithErrors;
        $data['executedTasksBoxs'] = $executedTasksBoxs;

        return $this->render('@DamisExperiment/Experiment/new.html.twig', $data);
    }

    /**
     * Delete experiments
     */
    #[Route("/delete.html", name: "experiment_delete", methods: ["POST"])]
    public function delete(Request $request)
    {
        /* @var $user \Base\UserBundle\Entity\User|null */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }
        
        $experimentsJson = $request->request->get('experiment-delete-list');
        $experiments = json_decode($experimentsJson);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($experiments)) {
             $this->addFlash('error', 'Invalid request data for deletion.');
             return $this->redirectToRoute('experiments_history');
        }


        $em = $this->doctrine->getManager();
        $deletedCount = 0;
        $projectDir = $this->getParameter('kernel.project_dir'); // Get project dir for unlinking files

        foreach ($experiments as $id) {
            if (!is_numeric($id)) continue; // Skip invalid IDs

            /* @var $experiment \Damis\ExperimentBundle\Entity\Experiment|null */
            $experiment = $em->getRepository(Experiment::class)->findOneBy(['id' => $id, 'user' => $user]); // Ensure user owns it
            
            if ($experiment) {
                // Find associated hidden dataset files BEFORE removing the experiment
                 $filesToDelete = [];
                 /* @var $task Workflowtask */
                 foreach($experiment->getWorkflowtasks() as $task) {
                     /* @var $pValue Parametervalue */
                     foreach($task->getParameterValues() as $pValue) {
                         // Check if it's an output parameter that might represent a file ID
                         if ($pValue->getParameter() && $pValue->getParameter()->getConnectionType() && $pValue->getParameter()->getConnectionType()->getId() == 2) {
                              $potentialFileId = $pValue->getParametervalue();
                              // Basic check if it looks like an ID - adjust as needed
                              if (is_numeric($potentialFileId)) { 
                                  $filesToDelete[] = (int)$potentialFileId;
                              }
                         }
                     }
                 }
                 $filesToDelete = array_unique($filesToDelete);


                // Remove the experiment (this should cascade to WorkflowTasks, ParameterValues etc. if set up)
                $em->remove($experiment);
                $deletedCount++;

                 // Now, try to find and delete the associated hidden dataset entities and files
                 if (!empty($filesToDelete)) {
                    $datasetRepo = $em->getRepository(Dataset::class);
                    foreach($filesToDelete as $fileId) {
                        /* @var $file Dataset|null */
                        $file = $datasetRepo->findOneBy(['datasetId' => $fileId, 'hidden' => true, 'user' => $user]);
                        if ($file) {
                            $filePath = $file->getFilePath();
                            if ($filePath) {
                                // Construct full path relative to project root (assuming assets are in public)
                                $fullPath = $projectDir . '/public' . $filePath; // Adjust '/public' if needed
                                if (file_exists($fullPath)) {
                                    if (@unlink($fullPath)) { // Use @ to suppress warnings if file is gone
                                         $this->logger->info("Deleted associated dataset file: {$fullPath}");
                                    } else {
                                         $this->logger->warning("Could not delete associated dataset file: {$fullPath}");
                                    }
                                } else {
                                     $this->logger->info("Associated dataset file not found, skipping unlink: {$fullPath}");
                                }
                            }
                             $em->remove($file); // Remove the Dataset entity
                             $this->logger->info("Removed associated hidden Dataset entity ID: {$fileId}");
                        }
                    }
                 }
            } else {
                 $this->logger->warning("User {$user->getId()} tried to delete non-existent or non-owned experiment ID: {$id}");
            }
        }
        
        if ($deletedCount > 0) {
            $em->flush(); // Flush all removals at once
            $this->addFlash('success', "Successfully deleted {$deletedCount} experiment(s).");
        } else {
             $this->addFlash('warning', 'No experiments were deleted. They might not exist or belong to you.');
        }

        return $this->redirectToRoute('experiments_history');
    }

    /**
     * Copy an experiment EXAMPLE to the current user's history
     */
    #[Route("/experiment/copy.html", name: "experiment_copy", methods: ["GET"])]
    public function copy(Request $request)
    {
        $this->midasService->checkSession();

        /* @var $user \Base\UserBundle\Entity\User|null */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }
        
        $experimentId = $request->query->getInt('experiment-example-id'); // Use getInt

        if ($experimentId <= 0) {
            $this->addFlash('error', 'Invalid experiment example ID.');
            return $this->redirectToRoute('experiments_examples');
        }


        $em = $this->doctrine->getManager();
        
        $experimentStatusExample = $em
            ->getRepository(Experimentstatus::class)
            ->findOneBy(['experimentstatus' => 'EXAMPLE']);

        if (!$experimentStatusExample) {
             $this->logger->error("EXAMPLE status not found in database.");
             $this->addFlash('error', 'System configuration error: EXAMPLE status missing.');
             return $this->redirectToRoute('experiments_examples');
        }
                  
        /* @var $experiment Experiment|null */
        $experiment = $this->doctrine
            ->getRepository(Experiment::class)
            ->findOneBy(['id' => $experimentId, 'status' => $experimentStatusExample]);
        
        if (!$experiment) {
            $this->logger->warning("User {$user->getId()} tried to copy non-existent or non-example experiment ID: {$experimentId}");
            $this->addFlash('error', 'Could not find the specified experiment example.');
            return $this->redirectToRoute('experiments_examples'); // Redirect to examples list
        }
      
        /* @var $newExperiment Experiment */
        $newExperiment = new Experiment();
        $newExperiment->setName($experiment->getName() . " (Copy)"); // Indicate it's a copy
        $newExperiment->setUser($user);
        $newExperiment->setUseCpu($experiment->getUseCpu());
        $newExperiment->setUsePrimaryMemory($experiment->getUsePrimaryMemory());
        $newExperiment->setUseSecMemory($experiment->getUseSecMemory());
                    
        $experimentStatusSaved = $em->getRepository(Experimentstatus::class)
                ->findOneBy(['experimentstatus' => 'SAVED']); // Find SAVED status
        
        if (!$experimentStatusSaved) {
            $this->logger->error("SAVED status not found in database.");
            $this->addFlash('error', 'System configuration error: SAVED status missing.');
            return $this->redirectToRoute('experiments_examples');
        }

        $newExperiment->setStatus($experimentStatusSaved); // Set status to SAVED
        
        $newExperiment->setGuiData($experiment->getGuiData()); // Copy GUI data
        
        $em->persist($newExperiment);
        $em->flush();
        
        $this->addFlash('success', $this->translator->trans('Experiment was copied', [], 'ExperimentBundle'));
        return $this->redirectToRoute('experiments_history'); // Redirect to user's history
    }

    /**
     * Copy a user's experiment to the list of EXAMPLES (Admin only)
     */
    #[Route("/experiment/example_copy.html", name: "experiment_example_copy", methods: ["GET"])]
    public function exampleCopy(Request $request)
    {
        $this->midasService->checkSession();
        
        /* @var $user \Base\UserBundle\Entity\User|null */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('fos_user_security_login');
        }
        
        // Use modern isGranted for security check
        if (!$this->isGranted('ROLE_ADMIN')) { 
            throw $this->createAccessDeniedException("Only administrators can create experiment examples.");
        }
        
        $experimentId = $request->query->getInt('experiment-example-id'); // Use getInt

         if ($experimentId <= 0) {
            $this->addFlash('error', 'Invalid experiment ID provided.');
            return $this->redirectToRoute('experiments_history'); // Redirect admin back to history
        }

        /* @var $experiment Experiment|null */
        // Admin can copy any user's experiment to examples
        $experiment = $this->doctrine 
            ->getRepository(Experiment::class)
            ->find($experimentId); // Find by ID regardless of owner for admin
      
        if (!$experiment) {
             $this->addFlash('error', "Experiment with ID {$experimentId} not found.");
             return $this->redirectToRoute('experiments_history');
        }

        // Prevent copying an existing example
        if ($experiment->getStatus() && $experiment->getStatus()->getExperimentstatus() === 'EXAMPLE') {
             $this->addFlash('warning', "Experiment ID {$experimentId} is already an example.");
             return $this->redirectToRoute('experiments_examples');
        }
        
        /* @var $newExperiment Experiment */
        $newExperiment = new Experiment();
        $newExperiment->setName($experiment->getName() . " (Example)"); // Indicate it's an example copy
        // Example experiments might not have a specific user, or use the admin? Assigning admin for now.
        $newExperiment->setUser($user); 
        $newExperiment->setUseCpu($experiment->getUseCpu());
        $newExperiment->setUsePrimaryMemory($experiment->getUsePrimaryMemory());
        $newExperiment->setUseSecMemory($experiment->getUseSecMemory());
        
        $em = $this->doctrine->getManager();
        
        $experimentStatusExample = $em->getRepository(Experimentstatus::class)
                ->findOneBy(['experimentstatus' => 'EXAMPLE']); // Find EXAMPLE status

        if (!$experimentStatusExample) {
            $this->logger->error("EXAMPLE status not found in database.");
            $this->addFlash('error', 'System configuration error: EXAMPLE status missing.');
            return $this->redirectToRoute('experiments_history');
        }
        
        $newExperiment->setStatus($experimentStatusExample); // Set status to EXAMPLE
        
        $newExperiment->setGuiData($experiment->getGuiData());
        
        $em->persist($newExperiment);
        $em->flush();
        
        $this->addFlash('success', $this->translator->trans('Experiment was copied to experiment examples', [], 'ExperimentBundle'));
        
        return $this->redirectToRoute('experiments_examples'); // Redirect to examples list
    }

    /**
     * Converts uploaded dataset files to ARFF format.
     * (Internal method, no route)
     */
    public function uploadArff($id)
    {
        $memoryLimit = ini_get('memory_limit');
        $suffix = '';
        sscanf($memoryLimit, '%u%c', $number, $suffix);
        if (isset($suffix)) {
            $number = $number * 1024 ** strpos(' KMG', (string) $suffix);
        }
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)
            ->findOneBy(['user' => $user, 'datasetId' => $id]);
        if ($entity) {
             $fileData = $entity->getFile();
             if (!$fileData || !isset($fileData['fileName']) || !isset($fileData['path'])) {
                 $this->addFlash('error', $this->translator->trans('Dataset file data is missing or incomplete.', [], 'DatasetsBundle'));
                 return false;
             }
             
            $fileName = $fileData['fileName'];
            $filePath = $fileData['path'];
             
            $format = pathinfo($fileName, PATHINFO_EXTENSION); // Use pathinfo for extension
            $filename = $entity->getDatasetTitle();

             // Use project directory parameter for reliable paths
             $projectDir = $this->getParameter('kernel.project_dir');
             $assetBasePath = $projectDir . '/public'; 
             $fullFilePath = $assetBasePath . $filePath; // Full path to the original file


            if ($format == 'zip') {
                $zip = new ZipArchive();
                if (!file_exists($fullFilePath)) {
                    $this->addFlash('error', $this->translator->trans('Zip file not found.', [], 'DatasetsBundle'));
                    return false;
                }

                $res = $zip->open($fullFilePath);
                if ($res !== true) {
                     $this->addFlash('error', $this->translator->trans('Failed to open zip file.', [], 'DatasetsBundle') . " Error code: {$res}");
                     return false;
                }
                
                if ($zip->numFiles === 0) {
                     $this->addFlash('error', $this->translator->trans('Zip file is empty.', [], 'DatasetsBundle'));
                     $zip->close();
                     return false;
                 }
                 
                if ($zip->numFiles > 1) {
                    $em->remove($entity); // Consider if removing entity is correct here
                    $em->flush();
                    $this->addFlash('error', $this->translator->trans('Zip file must contain exactly one file.', [], 'DatasetsBundle'));
                    $zip->close();
                    return false;
                }

                $extractedFileName = $zip->getNameIndex(0);
                $extractedFileFormat = pathinfo($extractedFileName, PATHINFO_EXTENSION);
                
                // Define extraction path relative to project (e.g., in cache or temp)
                 $extractionDir = $this->getParameter('kernel.cache_dir') . '/zip_extract_' . uniqid(); 
                 if (!mkdir($extractionDir, 0777, true) && !is_dir($extractionDir)) {
                     $this->addFlash('error', 'Failed to create temporary extraction directory.');
                     $zip->close();
                     return false;
                 }
                
                if ($zip->extractTo($extractionDir, $extractedFileName) === false) {
                    $this->addFlash('error', $this->translator->trans('Failed to extract file from zip.', [], 'DatasetsBundle'));
                    $zip->close();
                     // Optionally clean up $extractionDir
                     return false;
                 }
                $zip->close();
                 
                $extractedFilePath = $extractionDir . '/' . $extractedFileName;
                $fileReader = new ReadFile();
                $rows = null;

                if ($extractedFileFormat == 'arff') {
                    // If it's already ARFF, just rename/move and update entity
                    $rows = $fileReader->getRows($extractedFilePath, $extractedFileFormat); // Check validity/memory
                    if ($rows === false) {
                        $this->addFlash('error', $this->translator->trans('Exceeded memory limit processing extracted ARFF!', [], 'DatasetsBundle'));
                        unlink($extractedFilePath); // Clean up
                         // Should we remove the original entity? Maybe not.
                         return false;
                    }
                    unset($rows);

                     // Define new path for the ARFF file (relative to public/assets)
                     $newRelativeDir = dirname($filePath); // Keep original directory structure if desired
                     $newRelativePath = $newRelativeDir . '/' . pathinfo($fileName, PATHINFO_FILENAME) . '.arff'; // Rename zip to arff
                     $newFullPath = $assetBasePath . $newRelativePath;
                     
                     // Ensure directory exists
                     if (!file_exists(dirname($newFullPath))) {
                          mkdir(dirname($newFullPath), 0777, true);
                     }
                     
                    if (rename($extractedFilePath, $newFullPath)) {
                        $entity->setFilePath($newRelativePath); // Update path in entity
                         // Update file data array if needed (e.g., size, name)
                         $newFileData = $fileData;
                         $newFileData['fileName'] = basename($newFullPath);
                         $newFileData['path'] = $newRelativePath;
                         $newFileData['size'] = filesize($newFullPath);
                         $entity->setFile($newFileData); // Assuming setFile updates the stored array

                        $em->persist($entity);
                        $em->flush();
                        $this->addFlash('success', $this->translator->trans('Dataset successfully processed!', [], 'DatasetsBundle'));
                        // Clean up original zip? Optional. unlink($fullFilePath);
                        return true;
                    } else {
                        $this->addFlash('error', 'Failed to move extracted ARFF file.');
                         unlink($extractedFilePath); // Clean up extracted
                         return false;
                    }

                } elseif (in_array($extractedFileFormat, ['txt', 'tab', 'csv'])) {
                    $rows = $fileReader->getRows($extractedFilePath, $extractedFileFormat);
                     if ($rows === false) { // Memory check
                         $this->addFlash('error', $this->translator->trans('Dataset is too large or memory limit exceeded!', [], 'DatasetsBundle'));
                         unlink($extractedFilePath);
                         return false;
                     }
                    // Continue to ARFF conversion below...
                     unlink($extractedFilePath); // Delete extracted text file after reading rows

                } elseif (in_array($extractedFileFormat, ['xls', 'xlsx'])) {
                    try {
                        $objPHPExcel = PHPExcel_IOFactory::load($extractedFilePath);
                        $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                         array_unshift($rows, null); // Keep this? Seems like a way to adjust indices
                         unset($rows[0]);
                         unlink($extractedFilePath); // Delete extracted excel file
                         // Continue to ARFF conversion below...
                     } catch (\Exception $e) {
                         $this->addFlash('error', $this->translator->trans('Failed to read Excel file.', [], 'DatasetsBundle') . ' Error: ' . $e->getMessage());
                         unlink($extractedFilePath);
                         return false;
                     }
                } else {
                    // Invalid format inside zip
                    unlink($extractedFilePath);
                     // Should we remove the original entity? Maybe not. Let user delete it.
                     $this->addFlash('error', $this->translator->trans('Unsupported file format found inside zip!', [], 'DatasetsBundle') . " ({$extractedFileFormat})");
                    return false;
                }

            } elseif ($format == 'arff') {
                // Validate existing ARFF
                 if (!file_exists($fullFilePath)) {
                     $this->addFlash('error', $this->translator->trans('ARFF file not found.', [], 'DatasetsBundle'));
                     return false;
                 }
                 
                // Simple size check against memory (very approximate)
                if (memory_get_usage(true) + filesize($fullFilePath) * 5.8 > $number) {
                    $this->addFlash('error', $this->translator->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                    return false; // Don't remove entity for memory limit on existing file
                }
                
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows($fullFilePath, $format); // Check readability & basic format
                if ($rows === false) {
                    $this->addFlash('error', $this->translator->trans('Exceeded memory limit or invalid ARFF format!', [], 'DatasetsBundle'));
                    return false; // Don't remove entity
                }
                unset($rows); // Free memory
                
                // ARFF is already correct, maybe just confirm?
                 $entity->setFilePath($filePath); // Ensure path is set (might be redundant)
                $em->persist($entity);
                $em->flush();
                $this->addFlash('success', $this->translator->trans('Dataset successfully verified!', [], 'DatasetsBundle'));
                return true;

            } elseif (in_array($format, ['txt', 'tab', 'csv'])) {
                 if (!file_exists($fullFilePath)) {
                     $this->addFlash('error', $this->translator->trans('Dataset file not found.', [], 'DatasetsBundle'));
                     return false;
                 }
                $fileReader = new ReadFile();
                 if (memory_get_usage(true) + filesize($fullFilePath) * 5.8 > $number) {
                    $this->addFlash('error', $this->translator->trans('Dataset is too large!', [], 'DatasetsBundle'));
                    return false; // Don't remove entity
                }
                $rows = $fileReader->getRows($fullFilePath, $format);
                 if ($rows === false) { // Handle potential read errors beyond memory
                     $this->addFlash('error', $this->translator->trans('Failed to read dataset file.', [], 'DatasetsBundle'));
                     return false;
                 }
                 // Continue to ARFF conversion below...

            } elseif (in_array($format, ['xls', 'xlsx'])) {
                if (!file_exists($fullFilePath)) {
                    $this->addFlash('error', $this->translator->trans('Dataset file not found.', [], 'DatasetsBundle'));
                    return false;
                }
                 try {
                    $objPHPExcel = PHPExcel_IOFactory::load($fullFilePath);
                    $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                     array_unshift($rows, null);
                     unset($rows[0]);
                     // Continue to ARFF conversion below...
                 } catch (\Exception $e) {
                      $this->addFlash('error', $this->translator->trans('Failed to read Excel file.', [], 'DatasetsBundle') . ' Error: ' . $e->getMessage());
                     return false;
                 }
            } else {
                // Invalid initial format
                $this->addFlash('error', $this->translator->trans('Unsupported dataset format!', [], 'DatasetsBundle') . " ({$format})");
                 // Should we remove the entity? Maybe not.
                 return false;
            }

            if (!isset($rows) || empty($rows)) {
                 $this->addFlash('error', $this->translator->trans('No data found in the file to convert.', [], 'DatasetsBundle'));
                 return false;
             }

            $firstDataRowIndex = 1; // Assuming index 1 is the first row of data or headers
             if (!isset($rows[$firstDataRowIndex])) {
                 $this->addFlash('error', $this->translator->trans('Cannot determine headers or data types from file.', [], 'DatasetsBundle'));
                 return false;
             }

            $hasHeaders = false;
            foreach ($rows[$firstDataRowIndex] as $cell) {
                if (!is_numeric($cell)) {
                    $hasHeaders = true;
                    break;
                }
            }
            
            $secondDataRowIndex = $hasHeaders ? $firstDataRowIndex + 1 : $firstDataRowIndex;
             if (!isset($rows[$secondDataRowIndex])) {
                  $this->addFlash('warning', $this->translator->trans('Cannot determine data types, assuming string.', [], 'DatasetsBundle'));
                 // Proceed cautiously, maybe default all to string?
             }


            $arff = '@relation ' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . PHP_EOL . PHP_EOL; // Sanitize relation name

            $attributes = [];
            $headerRow = $hasHeaders ? $rows[$firstDataRowIndex] : $rows[$firstDataRowIndex]; // Use first row for headers or data type check

            foreach ($headerRow as $key => $headerValue) {
                $attributeName = $hasHeaders ? preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $headerValue) : 'attr' . $key;
                 // Prevent empty attribute names
                 if (empty(trim($attributeName))) {
                     $attributeName = 'attribute_' . $key;
                 }

                 $attributeType = 'string'; // Default type

                // Try to determine type from the *second* data row if headers exist, else first data row
                if (isset($rows[$secondDataRowIndex][$key])) {
                     $sampleValue = $rows[$secondDataRowIndex][$key];
                     if (is_numeric($sampleValue)) {
                          // More robust check for integer/real needed?
                          if (strpos((string)$sampleValue, '.') === false && strpos(strtolower((string)$sampleValue), 'e') === false) {
                              $attributeType = 'integer'; // Or 'numeric' if ARFF variant prefers
                          } else {
                              $attributeType = 'real'; // Or 'numeric'
                          }
                     } 
                     // Add date detection? ARFF has 'date' type.
                     // Add nominal detection? {val1, val2, ...}
                }
                
                $attributes[$key] = ['name' => $attributeName, 'type' => $attributeType];
                $arff .= '@attribute ' . $attributeName . ' ' . $attributeType . PHP_EOL;
            }
            
            $arff .= PHP_EOL . '@data' . PHP_EOL;

            if ($hasHeaders) {
                unset($rows[$firstDataRowIndex]); // Remove header row
            }

            foreach ($rows as $rowIndex => $row) {
                 if (count($row) !== count($attributes)) {
                      $this->logger->warning("Row {$rowIndex} in dataset ID {$id} has incorrect number of columns. Expected: " . count($attributes) . ", Found: " . count($row) . ". Skipping row.");
                     continue; // Skip rows with wrong column count
                 }
                 
                $rowData = [];
                 foreach ($row as $key => $value) {
                     // Basic ARFF value escaping (needs improvement for real edge cases)
                     $valueString = (string) $value;
                      // Handle missing values (ARFF uses '?')
                      if (trim($valueString) === '' || is_null($value)) {
                          $valueString = '?';
                      } else {
                          // Quote strings containing spaces, commas, or quotes
                          if ($attributes[$key]['type'] === 'string' && (str_contains($valueString, ' ') || str_contains($valueString, ',') || str_contains($valueString, "'") || str_contains($valueString, '"'))) {
                              // Simple quoting, might need more robust CSV-like escaping
                              $valueString = '"' . str_replace('"', '""', $valueString) . '"'; 
                          }
                      }
                      $rowData[] = $valueString;
                 }
                 $arff .= implode(',', $rowData) . PHP_EOL;
            }

            // Define path for the new ARFF file
             $newRelativeDir = dirname($filePath);
             $newRelativePath = $newRelativeDir . '/' . pathinfo($fileName, PATHINFO_FILENAME) . '.arff'; // Original filename + .arff
             $newFullPath = $assetBasePath . $newRelativePath;

             // Ensure directory exists
             if (!file_exists(dirname($newFullPath))) {
                  mkdir(dirname($newFullPath), 0777, true);
             }

            if (file_put_contents($newFullPath, $arff) !== false) {
                // Update entity
                 $entity->setFilePath($newRelativePath);
                 $newFileData = $fileData;
                 $newFileData['fileName'] = basename($newFullPath);
                 $newFileData['path'] = $newRelativePath;
                 $newFileData['size'] = filesize($newFullPath);
                 $entity->setFile($newFileData);

                 $em->persist($entity);
                 $em->flush();
                 $this->addFlash('success', $this->translator->trans('Dataset successfully converted and saved!', [], 'DatasetsBundle'));
                 
                 // Clean up original file? Optional.
                  if ($format !== 'zip') { // Don't delete original zip if that's what was uploaded initially
                  }
                  // unlink($fullFilePath); 
                 
                 return true;
            } else {
                 $this->addFlash('error', $this->translator->trans('Failed to write converted ARFF file!', [], 'DatasetsBundle'));
                 return false;
            }

        } // End if ($entity)
        
        $this->addFlash('error', $this->translator->trans('Dataset not found or access denied.', [], 'DatasetsBundle'));
        return false;
    }
}