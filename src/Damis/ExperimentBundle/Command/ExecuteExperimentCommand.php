<?php

namespace Damis\ExperimentBundle\Command;

use Damis\EntitiesBundle\Entity\Workflowtask;
use Damis\ExperimentBundle\Entity\Component;
use Damis\EntitiesBundle\Entity\Parametervalue;
use Damis\EntitiesBundle\Entity\Pvalueoutpvaluein;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\ExperimentBundle\Entity\Experimentstatus;
use Damis\DatasetsBundle\Entity\Dataset;
use Base\ConvertBundle\Helpers\ReadFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use DateTime;
use Exception;
use SoapClient;

#[AsCommand(
    name: 'experiment:execute',
    description: 'Execute experiment workflow tasks'
)]
class ExecuteExperimentCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private ContainerInterface $container;

    public function __construct(
        EntityManagerInterface $entityManager, 
        ParameterBagInterface $params,
        ContainerInterface $container
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->params = $params;
        // that are dynamically retrieved later.
        $this->container = $container;
    }

    protected function configure(): void
    {
        ini_set('date.timezone', "Europe/Vilnius");
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', "768M");
        ini_set('default_socket_timeout', 6000);
    }

    /**
     * Gets runnable tasks, runs them, and updates experiments statuses accordingly
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Executing workflow task');

        $em = $this->entityManager;

        //find specified number of executable workflow tasks (task not in progres|finished, parameter in not null, experiment - executing)
        $workflowTasks = $em->getRepository(Workflowtask::class)->getRunnableTasks(100);

        //for all found workflow tasks
        /* @var $task \Damis\EntitiesBundle\Entity\Workflowtask */
        foreach ($workflowTasks as $task) {
            //set to in progress
            $task->setWorkflowtaskisrunning(1);//running
            $em->flush();

            // collect all data

            //find damned component
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em->getRepository(Component::class)->getTasksComponent($task);
            if (!$component) {
                continue;
            }

            $output->writeln('==============================');
            $output->writeln('Task id : '.$task->getWorkflowtaskid());
            $output->writeln('Wsdl host : '.$component->getWsdlRunHost());
            $output->writeln('Wsdl function : '.$component->getWsdlCallFunction());

            // filter out un callable functions
            if (!$component->getWsdlRunHost()) {//locally executable actions
                if ($component->getWsdlCallFunction() == 'SELECT') {
                    $selAttr = $em->getRepository(Parametervalue::class)->getValueBySlug($task, 'selAttr')['parametervalue'];
                    $classAttr = $em->getRepository(Parametervalue::class)->getValueBySlug($task, 'classAttr')['parametervalue'];
                    $inAttr = $em->getRepository(Parametervalue::class)->getValueByType($task, 1)['parametervalue'];
                    $outAttrEntity = $em->getRepository(Parametervalue::class)->getParameterByType($task, 2);
                    
                    if ($inAttr === null or $selAttr === null or $classAttr === null or $outAttrEntity == null) {
                        $output->writeln('Missing task parameters, closing.');
                        $task->setWorkflowtaskisrunning(3);//error
                        $task->setMessage('Missing task parameters');
                    } else {
                        $fileSelect = new ReadFile();
                        $selAttr = json_decode((string) $selAttr);
                        $classAttr = json_decode((string) $classAttr);

                        $processedFileId = $fileSelect->selectFeatures(
                            $inAttr,
                            $selAttr,
                            $classAttr,
                            $task->getExperiment()->getUser()->getId(),
                            $this->container 
                        );

                        // set proper out and in if available and successfull
                        $outAttrEntity->setParametervalue($processedFileId);
                        $inNexts = $em->getRepository(Pvalueoutpvaluein::class)->findBy(['outparametervalue' => $outAttrEntity->getParametervalueid()]);
                        foreach ($inNexts as $inNext) {
                            $inNext->getInparametervalue()->setParametervalue($processedFileId);
                        }

                        $output->writeln('Task local, done.');
                        $task->setWorkflowtaskisrunning(2);//finished
                    }
                } else {
                    //set to finished
                    $output->writeln('Unrunnable task, closing.');
                    $task->setWorkflowtaskisrunning(2);//finished
                }

                $em->flush();
                continue;
            }

            $params = [];

            $inDatasetEntity = null;
            $outDatasetEntities = null;
            foreach ($em->getRepository(Parametervalue::class)->getOrderedParameters($task) as $value) {
                if ($value->getParameter()->getConnectionType()->getId() == 1) {
                    $inDatasetEntity = $value;
                }
                if ($value->getParameter()->getConnectionType()->getId() == 2) {
                    $outDatasetEntities[$value->getParameter()->getSlug()] = $value;
                }
                if ($value->getParameter()->getConnectionType()->getId() == 3) {
                    $params[$value->getParameter()->getSlug()] = $value->getParametervalue();
                }
            }

            if (!$inDatasetEntity) {
                continue;
            }
            $dataset = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $inDatasetEntity->getParametervalue()]);
            if (!$dataset) {
                continue;
            }

            $calcTime = 0;
            if ($task->getExperiment()->getMaxDuration() and $task->getExperiment()->getMaxDuration() instanceof DateTime) {
                $calcTime = $this->hoursToSecods($task->getExperiment()->getMaxDuration()->format('H:i:s'));
            }

            $proc = [];
            if ($component->getWsdlCallFunction() == 'MLP' or
                $component->getWsdlCallFunction() == 'SMACOFMDS' or
                $component->getWsdlCallFunction() == 'SAMANN' or
                $component->getWsdlCallFunction() == 'SOM'
            ) {
                if ($task->getExperiment()->getUseCpu()) {
                    $proc['p'] = $task->getExperiment()->getUseCpu();
                } else {
                    $proc['p'] = 1;
                }
            }

            // URL-encode the filename to handle spaces and special characters
            $filePath = $dataset->getFilePath();
            $encodedPath = str_replace(' ', '%20', $filePath);

            $params = array_merge(
                ['X' => $this->params->get('project_full_host').$encodedPath],
                $params,
                $proc,
                ['maxCalcTime' => $calcTime]
            );
            if (!$params['maxCalcTime']) {
                $params['maxCalcTime'] = 1;
            }

            // Transform MLP parameters from form format to C service format
            if ($component->getWsdlCallFunction() == 'MLP') {
                $qty = $params['qty'] ?? 90;
                $kFold = $params['kFoldValidation'] ?? 0;

                if ($kFold == 0) {
                    $dV = 1;
                    $dT = max(1, floor((100 - $qty) * 0.9));
                    $dL = 100 - $dT - $dV;
                } else {
                    $dL = 80;
                    $dT = 10;
                    $dV = 10;
                }

                $params = [
                    'X' => $params['X'],
                    'h1pNo' => $params['h1pNo'],
                    'h2pNo' => $params['h2pNo'],
                    'h3pNo' => 0,
                    'dL' => $dL,
                    'dT' => $dT,
                    'dV' => $dV,
                    'maxIteration' => $params['maxIteration'],
                    'p' => $params['p'],
                    'maxCalcTime' => $params['maxCalcTime']
                ];
            }

            $output->writeln('Wsdl function parameters: '.print_r($params, true));
                
            //FOR TESTING PURPOSES ONLY
            //$params['X'] = 'http://158.129.140.146/Damis/Data/testData/test.arff';

            // execute

            /* @var $client \SoapClient */
            // Create SSL context to handle HTTPS connections (including self-signed certificates)
            $sslContext = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
                'http' => [
                    'timeout' => 3600,
                ]
            ]);

            $client = new \SoapClient(
                $component->getWsdlRunHost(),
                [
                    'trace' => 1,
                    'exception' => 0,
                    'connection_timeout' => 3600,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'stream_context' => $sslContext
                ]
            );

            $result = false;
            $error = false;
            try {
                //@TODO SSL implementation
                $output->writeln('Starting call to wsdl function');
                $result = @$client->__soapCall($component->getWsdlCallFunction(), $params);
                $output->writeln('End of call to wsdl function');
            } catch (\SoapFault $e) {
                $error['message'] = $e->getMessage();
                $error['detail'] = @$e->detail;
            }

            // process result

            if ($error) {
                //save error message
                $task->setWorkflowtaskisrunning(3);//error!
                $task->setMessage($error['message'].':'.$error['detail']);
                $output->writeln('Wsdl result error: '.print_r($error, true));
                $em->flush();
                continue;
            } else {
                // set proper execution time
                $task->setExecutionTime($result['calcTime']);
                if (isset($result['algorithmError'])) {
                    $task->setMessage($result['algorithmError']);
                }

                // saing received files
                $temp_folder = $this->params->get("kernel.cache_dir");

                //Y
                $output->writeln("DEBUG: C++ returned URL: " . ($result['Y'] ?? '[missing]')); // See exactly what we are fetching
                
                $temp_file_y = $temp_folder.'/'.basename((string) ($result['Y'] ?? ''));
                $err_y = false;
                
                if (empty($result['Y'])) {
                    $err_y = true;
                    $output->writeln("ERROR DETAILS: Empty Y URL returned from WSDL call");
                } else {
                    try {
                        $arrContextOptions = [
                            "ssl" => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ],
                            "http" => [
                                "timeout" => 60, // Wait up to 60 seconds
                                "ignore_errors" => true, // Fetch content even on 404/500 to see error page
                                "follow_location" => 1, // Follow HTTP redirects (302, 301, etc.)
                                "max_redirects" => 5
                            ]
                        ];

                        // Attempt download
                        $content = @file_get_contents($result['Y'], false, stream_context_create($arrContextOptions));

                        if ($content === false) {
                            $error = error_get_last();
                            throw new Exception("Download failed: " . ($error['message'] ?? 'Unknown error'));
                        }

                        // After redirects, check the final HTTP status (last HTTP/x.x line in headers)
                        $finalStatus = '';
                        foreach ($http_response_header ?? [] as $header) {
                            if (strpos($header, 'HTTP/') === 0) {
                                $finalStatus = $header;
                            }
                        }
                        if (strpos($finalStatus, '200') === false) {
                             throw new Exception("HTTP Error: " . ($finalStatus ?: 'Unknown HTTP status'));
                        }

                        file_put_contents($temp_file_y, $content);
                        $output->writeln("DEBUG: File saved successfully to " . $temp_file_y);

                    } catch (Exception $e) {
                        $err_y = true;
                        $output->writeln("ERROR DETAILS: " . $e->getMessage()); 
                    }
                }

                //Yalt
                $err_yalt = false;
                if (isset($result['Yalt'])) {
                    // Treat empty string the same as "no Yalt" (optional output)
                    if (empty($result['Yalt'])) {
                        $output->writeln("INFO: Yalt URL not provided, skipping optional output");
                    } else {
                        $temp_file_yalt = $temp_folder.'/'.basename((string) $result['Yalt']);
                        try {
                            // Use same context options as Y file (SSL + redirect handling)
                            $arrContextOptionsYalt = [
                                "ssl" => [
                                    "verify_peer" => false,
                                    "verify_peer_name" => false,
                                ],
                                "http" => [
                                    "timeout" => 60,
                                    "follow_location" => 1,
                                    "max_redirects" => 5
                                ]
                            ];
                            $contentYalt = @file_get_contents($result['Yalt'], false, stream_context_create($arrContextOptionsYalt));
                            if ($contentYalt !== false) {
                                file_put_contents($temp_file_yalt, $contentYalt);
                            } else {
                                throw new Exception("Failed to download Yalt file");
                            }
                        } catch (Exception $e) {
                            $err_yalt = true;
                            $output->writeln("ERROR DETAILS Yalt: " . $e->getMessage());
                        }
                    }
                }

                if ($err_y == false and $err_yalt == false) {
                    //create dataset Y
                    $file_y = new File($temp_file_y);

                    $file_entity_y = new Dataset();
                    $file_entity_y->setUser($task->getExperiment()->getUser());
                    $file_entity_y->setDatasetTitle('experiment result');
                    $file_entity_y->setDatasetCreated(time());
                    $file_entity_y->setDatasetIsMidas(false);
                    $file_entity_y->setHidden(true);
                    $em->persist($file_entity_y);
                    $em->flush();//HACK, ENTITY MUST BE PERSISTED, FOR MANUAL UPLOAD TO WORK

                    $this->saveDatasetFile($file_entity_y, $file_y);
                    $em->flush();

                    @unlink($temp_file_y);

                    if (!empty($result['Yalt'])) {
                        //create dataset Yalt
                        $file_alt = new File($temp_file_yalt);

                        $file_entity_alt = new Dataset();
                        $file_entity_alt->setUser($task->getExperiment()->getUser());
                        $file_entity_alt->setDatasetTitle('experiment result');
                        $file_entity_alt->setDatasetCreated(time());
                        $file_entity_alt->setDatasetIsMidas(false);
                        $file_entity_alt->setHidden(true);
                        $em->persist($file_entity_alt);
                        $em->flush();
//
                        $this->saveDatasetFile($file_entity_alt, $file_alt);
                        $em->flush();

                        @unlink($temp_file_yalt);
                    }

                    // set proper out and in if available and successfull
                    if (isset($outDatasetEntities['Y'])) {
                        $outDatasetEntities['Y']->setParametervalue($file_entity_y->getDatasetId());

                        $inNexts = $em->getRepository(Pvalueoutpvaluein::class)->findBy(['outparametervalue' => $outDatasetEntities['Y']->getParametervalueid()]);
                        foreach ($inNexts as $inNext) {
                            $inNext->getInparametervalue()->setParametervalue($file_entity_y->getDatasetId());
                        }
                    }

                    if (isset($outDatasetEntities['Yalt']) && isset($file_entity_alt)) {
                        $outDatasetEntities['Yalt']->setParametervalue($file_entity_alt->getDatasetId());

                        $inNexts = $em->getRepository(Pvalueoutpvaluein::class)->findBy(['outparametervalue' => $outDatasetEntities['Yalt']->getParametervalueid()]);
                        foreach ($inNexts as $inNext) {
                            $inNext->getInparametervalue()->setParametervalue($file_entity_alt->getDatasetId());
                        }
                    }

                } else {
                    $task->setWorkflowtaskisrunning(3);//error!
                    $task->setMessage('Save file error');
                    $output->writeln('Save file error');
                    $em->flush();
                    continue;
                }

                $output->writeln('Wsdl result got: '.print_r($result, true));
            }


            //set to finished
            $output->writeln('Task finished, closing.');
            $task->setWorkflowtaskisrunning(2);//finished
            $em->flush();
        }

        // find tasks which cannot be run, i.e. in file tasks and set to finished

        $workflowTasksUn = $em->getRepository(Workflowtask::class)->getUnrunableTasks(100);
        foreach ($workflowTasksUn as $taskUn) {
            // Check for missing parameter values for Uploaded File components (Type 1)
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em->getRepository(Component::class)->getTasksComponent($taskUn);
            if ($component && $component->getTypeId()->getId() == 1) { // 1 = Uploaded File
                foreach ($taskUn->getParameterValues() as $pValue) {
                    if ($pValue->getParameter()->getConnectionType()->getId() == 2 // Output
                        && empty($pValue->getParametervalue())) {
                        
                        $output->writeln("Attempting to fix missing parameter for task " . $taskUn->getWorkflowtaskid());
                        
                        $guiDataRaw = (string) $taskUn->getExperiment()->getGuiData();
                        $guiDataParts = explode('***', $guiDataRaw);
                        $guiData = json_decode($guiDataParts[0], true);
                        
                        if (!$guiData) {
                             $output->writeln("GUIData is empty or invalid JSON");
                        } else {
                             $boxId = $taskUn->getTaskBox();
                             $output->writeln("Looking for boxId: " . $boxId);
                             
                             if (isset($guiData[$boxId]['form_parameters'])) {
                                 $output->writeln("Found form_parameters for box " . $boxId);
                                 if (is_array($guiData[$boxId]['form_parameters'])) {
                                    foreach ($guiData[$boxId]['form_parameters'] as $fp) {
                                        if (isset($fp['value']) && $fp['value']) {
                                            $val = $fp['value'];
                                            $pValue->setParametervalue($val);
                                            $em->persist($pValue);
                                            $output->writeln("Fixed parameter value: " . $val);
                                            break; 
                                        }
                                    }
                                 }
                            } else {
                                 $output->writeln("No form_parameters found for box " . $boxId);
                            }
                        }
                    }
                }
            }

            $output->writeln('==============================');
            $output->writeln('Task id : '.$taskUn->getWorkflowtaskid());
            $output->writeln('Un runable task, set to finish.');
            
            foreach ($taskUn->getParameterValues() as $pValue) {
                $param = $pValue->getParameter();
                if ($param && $param->getConnectionType() && $param->getConnectionType()->getId() == 2) {
                     $inNexts = $em->getRepository(Pvalueoutpvaluein::class)->findBy(['outparametervalue' => $pValue->getParametervalueid()]);
                     foreach ($inNexts as $inNext) {
                         $inNext->getInparametervalue()->setParametervalue($pValue->getParametervalue());
                     }
                }
            }

            $taskUn->setWorkflowtaskisrunning(2);//finished
            $em->persist($taskUn);
        }
        $em->flush();

        // find finished experiments and set to finished

        $experimentsToCloe = $em->getRepository(Experiment::class)->getClosableExperiments(100);
        $experimentStatus = $em
            ->getRepository(Experimentstatus::class)
            ->findOneBy(['experimentstatus' => 'FINISHED']);
        foreach ($experimentsToCloe as $exCl) {
            $output->writeln('==============================');
            $output->writeln('Experiment id : '.$exCl->getId());
            $output->writeln('Set to finished, has all tasks finished.');
            $exCl->setStatus($experimentStatus);//finished
            $exCl->setFinish(time());
            $em->persist($exCl);
        }
        $em->flush();

        // find errored experiments and set to error

        $experimentsToCloe = $em->getRepository(Experiment::class)->getClosableErrExperiments(100);
        $experimentStatus = $em
            ->getRepository(Experimentstatus::class)
            ->findOneBy(['experimentstatus' => 'ERROR']);
        foreach ($experimentsToCloe as $exCl) {
            $output->writeln('==============================');
            $output->writeln('Experiment id : '.$exCl->getId());
            $output->writeln('Set to error, has error in one of the tasks.');
            $exCl->setStatus($experimentStatus);//finished
            $exCl->setFinish(time());
            $em->persist($exCl);
        }
        $em->flush();


        $output->writeln('==============================');
        $output->writeln('Executing finished');

        return Command::SUCCESS;
    }

    private function saveDatasetFile(Dataset $dataset, File $file): void
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $targetDir = $projectDir . '/public/uploads/datasets';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $originalName = $file->getFilename();
        // Ensure we have a safe filename
        $newName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $originalName);
        
        $file->move($targetDir, $newName);
        
        $relativePath = '/uploads/datasets/' . $newName;
        $dataset->setFilePath($relativePath);
        $dataset->setFile(['path' => $relativePath]);
    }

    private function hoursToSecods($hour): int
    {
    // $hour must be a string type: "HH:mm:ss"
        $parse = [];
        if (!preg_match('#^(?<hours>[\d]{2}):(?<mins>[\d]{2}):(?<secs>[\d]{2})$#', (string) $hour, $parse)) {
            return 0;
        }

        return (int) $parse['hours'] * 3600 + (int) $parse['mins'] * 60 + (int) $parse['secs'];
    }
}
