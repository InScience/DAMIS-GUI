<?php

namespace Damis\ExperimentBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use ReflectionClass;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\HttpFoundation\File\File;
use Damis\DatasetsBundle\Entity\Dataset;
use Base\ConvertBundle\Helpers\ReadFile;

class ExecuteExperimentCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('experiment:execute')
            ->setDescription('Execute experiment workflow tasks')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Executing workflow task');

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager('default');

        //find specified number of executable workflow tasks (task not in progres|finished, parameter in not null, experiment - executing)
        $workflowTasks = $em->getRepository('DamisEntitiesBundle:Workflowtask')->getRunnableTasks(100);

        //for all found workflow tasks
        /* @var $task \Damis\EntitiesBundle\Entity\Workflowtask */
        foreach($workflowTasks as $task){
            //set to in progress
            $task->setWorkflowtaskisrunning(1);//running
            $em->flush();

            //----------------------------------------------------------------------------------------------------//
            // collect all data
            //----------------------------------------------------------------------------------------------------//

            //find damned component
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em->getRepository('DamisExperimentBundle:Component')->getTasksComponent($task);
            if (!$component) continue;

            $output->writeln('==============================');
            $output->writeln('Task id : ' . $task->getWorkflowtaskid());
            $output->writeln('Wsdl host : ' . $component->getWsdlRunHost());
            $output->writeln('Wsdl function : ' . $component->getWsdlCallFunction());

            // filter out un callable functions
            if (!$component->getWsdlRunHost()) {
                if ($component->getWsdlCallFunction() == 'SELECT'){
                    $selAttr = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getValueBySlug($task, 'selAttr')['parametervalue'];
                    $classAttr = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getValueBySlug($task, 'classAttr')['parametervalue'];
                    $inAttr = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getValueByType($task, 1)['parametervalue'];
                    $outAttrEntity = $em->getRepository('DamisEntitiesBundle:Parametervalue')->getParameterByType($task, 2);

                    if ($inAttr === NULL or $selAttr === NULL or $classAttr === NULL or $outAttrEntity == NULL){
                        $output->writeln('Missing task parameters, closing.');
                        $task->setWorkflowtaskisrunning(3);//error
                        $task->setMessage('Missing task parameters');
                    } else {
                        $fileSelect = new ReadFile();
                        $selAttr = json_decode($selAttr);
                        $classAttr = json_decode($classAttr);

                        $processedFileId = $fileSelect->selectFeatures(
                            $inAttr,
                            $selAttr,
                            $classAttr,
                            $task->getExperiment()->getUser()->getId(),
                            $this->getContainer()
                        );

                        // set proper out and in if available and successfull
                        $outAttrEntity->setParametervalue($processedFileId);
                        $inNexts = $em->getRepository('DamisEntitiesBundle:Pvalueoutpvaluein')->findBy(array('outparametervalue' => $outAttrEntity->getParametervalueid()));
                        foreach($inNexts as $inNext) {
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

            $params = array();

            $inDatasetEntity = null;
            $outDatasetEntity = null;
            foreach($em->getRepository('DamisEntitiesBundle:Parametervalue')->getOrderedParameters($task) as $value){
                if ($value->getParameter()->getConnectionType()->getId() == 1)
                    $inDatasetEntity = $value;
                if ($value->getParameter()->getConnectionType()->getId() == 2)
                    $outDatasetEntity = $value;
                if ($value->getParameter()->getConnectionType()->getId() == 3)
                    $params[$value->getParameter()->getSlug()] = $value->getParametervalue();
            }

            if (!$inDatasetEntity) continue;
            $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneBy(['datasetId' => $inDatasetEntity->getParametervalue()]);
            if (!$dataset) continue;


            $calcTime = 0;
            if ($task->getExperiment()->getMaxDuration() and $task->getExperiment()->getMaxDuration() instanceof DateTime)
                $calcTime = $this->hoursToSecods($task->getExperiment()->getMaxDuration()->format('H:i:s'));

            $proc = array();
            if ($component->getWsdlCallFunction() == 'MLP' OR
                $component->getWsdlCallFunction() == 'SMACOFMDS' OR
                $component->getWsdlCallFunction() == 'SAMANN' OR
                $component->getWsdlCallFunction() == 'SOM'
            ){
                if ($task->getExperiment()->getUseCpu())
                    $proc['P'] = $task->getExperiment()->getUseCpu();
                else
                    $proc['P'] = 1;
            }


            $params = array_merge(
                array(
                    'X' => $this->getContainer()->getParameter('project_full_host') . $dataset->getFilePath(),
                ),
                $params,
                $proc,
                array(
                    'maxCalcTime' => $calcTime,
                )
            );
            if (!$params['maxCalcTime']) $params['maxCalcTime'] = 1;

            //----------------------------------------------------------------------------------------------------//

            $output->writeln('Wsdl function parameters: ' . print_r($params, true));

            //FOR TESTING PURPOSES ONLY
            //$params['X'] = 'http://158.129.140.146/Damis/Data/testData/test.arff';

            //----------------------------------------------------------------------------------------------------//
            // execute
            //----------------------------------------------------------------------------------------------------//

            /* @var $client \SoapClient */
            $client = new \SoapClient($component->getWsdlRunHost(),
                array(
                    'trace' => 1,
                    'exception' => 0
                )
            );

            $result = false;
            $error = false;
            try {
                $result = @$client->__soapCall($component->getWsdlCallFunction(), $params);
            } catch (\SoapFault $e) {
                $error['message'] = $e->getMessage();
                $error['detail'] = @$e->detail;
            }

            //----------------------------------------------------------------------------------------------------//
            // process result
            //----------------------------------------------------------------------------------------------------//

            if ($error) {
                //save error message
                $task->setWorkflowtaskisrunning(3);//error!
                $task->setMessage($error['message'] . ':' . $error['detail']);
                $output->writeln('Wsdl result error: ' . print_r($error, true));
                $em->flush();
                continue;
            } else {
                // set proper execution time
                $task->setExecutionTime($result['calcTime']);
                if (isset($result['algorithmError']))
                    $task->setMessage($result['algorithmError']);

                // save results file
                $temp_folder = $this->getContainer()->getParameter("kernel.cache_dir");
                $temp_file = $temp_folder . '/' . basename($result['Y']);
                $err = false;
                try {
                    file_put_contents($temp_file, file_get_contents($result['Y']));
                } catch (Exception $e) {
                    $err = true;
                }

                if ($err == false) {
                    //create dataset
                    $file = new File($temp_file);

                    $file_entity = new Dataset();
                    $file_entity->setUserId($task->getExperiment()->getUser());
                    $file_entity->setDatasetTitle('experiment result');
                    $file_entity->setDatasetCreated(time());
                    $file_entity->setDatasetIsMidas(false);
                    $file_entity->setHidden(true);
                    $em->persist($file_entity);
                    $em->flush();//HACK, ENTITY MUST BE PERSISTED, FOR MANUAL UPLOAD TO WORK

                    $ref_class = new ReflectionClass('Damis\DatasetsBundle\Entity\Dataset');
                    $mapping = $this->getContainer()->get('iphp.filestore.mapping.factory')->getMappingFromField($file_entity, $ref_class, 'file');
                    $file_data = $this->getContainer()->get('iphp.filestore.filestorage.file_system')->upload($mapping, $file);
                    $file_entity->setFile($file_data);
                    $file_entity->setFilePath($file_data['path']);
                    $em->flush();

                    @unlink($temp_file);

                    // set proper out and in if available and successfull
                    if ($outDatasetEntity){
                        $outDatasetEntity->setParametervalue($file_entity->getDatasetId());

                        $inNexts = $em->getRepository('DamisEntitiesBundle:Pvalueoutpvaluein')->findBy(array('outparametervalue' => $outDatasetEntity->getParametervalueid()));
                        foreach($inNexts as $inNext) {
                            $inNext->getInparametervalue()->setParametervalue($file_entity->getDatasetId());
                        }
                    }

                } else {
                    $task->setWorkflowtaskisrunning(3);//error!
                    $task->setMessage('Save file error');
                    $output->writeln('Save file error');
                    $em->flush();
                    continue;
                }

                $output->writeln('Wsdl result got: ' . print_r($result, true));
            }

            //----------------------------------------------------------------------------------------------------//

            //set to finished
            $output->writeln('Task finished, closing.');
            $task->setWorkflowtaskisrunning(2);//finished
            $em->flush();
        }

        //----------------------------------------------------------------------------------------------------//
        // find tasks which cannot be run, i.e. in file tasks and set to finished
        //----------------------------------------------------------------------------------------------------//

        $workflowTasksUn = $em->getRepository('DamisEntitiesBundle:Workflowtask')->getUnrunableTasks(100);
        foreach($workflowTasksUn as $taskUn){
            $output->writeln('==============================');
            $output->writeln('Task id : ' . $taskUn->getWorkflowtaskid());
            $output->writeln('Un runable task, set to finish.');
            $taskUn->setWorkflowtaskisrunning(2);//finished
        }
        $em->flush();

        //----------------------------------------------------------------------------------------------------//
        // find finished experiments and set to finished
        //----------------------------------------------------------------------------------------------------//

        $experimentsToCloe = $em->getRepository('DamisExperimentBundle:Experiment')->getClosableExperiments(100);
        $experimentStatus = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'FINISHED']);
        foreach($experimentsToCloe as $exCl){
            $output->writeln('==============================');
            $output->writeln('Experiment id : ' . $exCl->getId());
            $output->writeln('Set to finished, has all tasks finished.');
            $exCl->setStatus($experimentStatus);//finished
        }
        $em->flush();

        //----------------------------------------------------------------------------------------------------//
        // find errored experiments and set to error
        //----------------------------------------------------------------------------------------------------//

        $experimentsToCloe = $em->getRepository('DamisExperimentBundle:Experiment')->getClosableErrExperiments(100);
        $experimentStatus = $em
            ->getRepository('DamisExperimentBundle:Experimentstatus')
            ->findOneBy(['experimentstatus' => 'ERROR']);
        foreach($experimentsToCloe as $exCl){
            $output->writeln('==============================');
            $output->writeln('Experiment id : ' . $exCl->getId());
            $output->writeln('Set to finished, has all tasks finished.');
            $exCl->setStatus($experimentStatus);//finished
        }
        $em->flush();

        //----------------------------------------------------------------------------------------------------//

        $output->writeln('==============================');
        $output->writeln('Executing finished');
    }

    function hoursToSecods ($hour) { // $hour must be a string type: "HH:mm:ss"
        $parse = array();
        if (!preg_match ('#^(?<hours>[\d]{2}):(?<mins>[\d]{2}):(?<secs>[\d]{2})$#',$hour,$parse)) {
            return 0;
        }

        return (int) $parse['hours'] * 3600 + (int) $parse['mins'] * 60 + (int) $parse['secs'];
    }

}