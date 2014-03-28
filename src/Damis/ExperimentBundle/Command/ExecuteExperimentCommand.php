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
            //$em->flush(); //COMMENT FOR TESTING PURPOSES ONLY

            //----------------------------------------------------------------------------------------------------//
            // collect all data
            //----------------------------------------------------------------------------------------------------//

            //find damned component
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em->getRepository('DamisExperimentBundle:Component')->getTasksComponent($task);
            if (!$component) continue;

            $params = array();

            $inDataset = null;
            foreach($em->getRepository('DamisEntitiesBundle:Parametervalue')->getOrderedParameters($task) as $value){
                if ($value->getParameter()->getConnectionType()->getId() == 1)
                    $inDataset = $value->getParametervalue();
                if ($value->getParameter()->getConnectionType()->getId() == 3)
                    $params[$value->getParameter()->getSlug()] = $value->getParametervalue();
            }

            if (!$inDataset) continue;
            $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneBy(['datasetId' => $inDataset]);
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

            $output->writeln('Task id : ' . $task->getWorkflowtaskid());
            $output->writeln('Wsdl host : ' . $component->getWsdlRunHost());
            $output->writeln('Wsdl function : ' . $component->getWsdlCallFunction());
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
            } else {
                // set proper execution time
                $task->setExecutionTime($result['calcTime']);

                // save results file

                // set proper out and in if available and successfull

                $output->writeln('Wsdl result got: ' . print_r($result, true));
            }

            //----------------------------------------------------------------------------------------------------//

            //set to finished
            $task->setWorkflowtaskisrunning(2);//finished
            //$em->flush(); //COMMENT FOR TESTING PURPOSES ONLY
        }

        //find finished experiments and set to finished

        $output->writeln('Executing finished');
    }

}