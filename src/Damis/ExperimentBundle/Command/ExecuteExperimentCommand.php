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
            if ($component->getWsdlCallFunction() == "CHART") {
                //set to finished
                $task->setWorkflowtaskisrunning(2);//finished
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

            $params = array_merge(
                array(
                    'X' => $this->getContainer()->getParameter('project_full_host') . $dataset->getFilePath(),
                ),
                $params,
                array(
                    'maxCalcTime' => $task->getExperiment()->getMaxDuration()
                )
            );
            if (!$params['maxCalcTime']) $params['maxCalcTime'] = 1;

            //----------------------------------------------------------------------------------------------------//

            $output->writeln('Wsdl function parameters: ' . print_r($params, true));

            //FOR TESTING PURPOSES ONLY
            $params['X'] = 'http://158.129.140.146/Damis/Data/testData/test.arff';

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

                    // set proper out and in if available and successfull
                    if ($outDatasetEntity){
                        $outDatasetEntity->setParametervalue($file_entity->getDatasetId());

                        $inNext = $em->getRepository('DamisEntitiesBundle:Pvalueoutpvaluein')->findOneBy(array('outparametervalue' => $outDatasetEntity->getParametervalueid()));
                        if ($inNext) {
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
            $task->setWorkflowtaskisrunning(2);//finished
            $em->flush();
        }

        //find finished experiments and set to finished
        $workflowTasksUn = $em->getRepository('DamisEntitiesBundle:Workflowtask')->getUnrunableTasks(100);
        foreach($workflowTasksUn as $taskUn){
            $output->writeln('==============================');
            $output->writeln('Task id : ' . $taskUn->getWorkflowtaskid());
            $output->writeln('Set to finished, has no in parameters.');
            $taskUn->setWorkflowtaskisrunning(2);//finished
        }
        $em->flush();

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

        $output->writeln('==============================');
        $output->writeln('Executing finished');
    }

}