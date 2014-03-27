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
    const url_pre = 'http://www.damis.lt';

    protected function configure() {
        $this
            ->setName('experiment:execute')
            ->setDescription('Execute experiment workflow tasks')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Executing workflow task');

//        $weeks_length = $input->getArgument('weeks_length');

//        if (!$weeks_length) {
//            $weeks_length = 3;
//        } else {
//            $weeks_length = (int)$weeks_length;
//        }
//        if ($weeks_length < 1) $weeks_length = 3;

//        $date_from = new DateTime(date('Y-m-d'), new \DateTimeZone('Europe/Vilnius'));


        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager('default');

        //find specified number of executable workflow tasks (task not in progres|finished, parameter in not null, experiment - executing)
        $workflowTasks = $em->getRepository('DamisEntitiesBundle:Workflowtask')->getRunnableTasks(100);

        //for all found workflow tasks
        /* @var $task \Damis\EntitiesBundle\Entity\Workflowtask */
        foreach($workflowTasks as $task){
            //set to in progress
            $task->setWorkflowtaskisrunning(1);//running
            //$em->flush(); //FOR TESTING PURPOSES ONLY

            //collect all data

            //find damned component
            /* @var $component \Damis\ExperimentBundle\Entity\Component */
            $component = $em->getRepository('DamisExperimentBundle:Component')->getTasksComponent($task);
            if (!$component) continue;

            $params = array();
            $inDataset = null;
            foreach($task->getParameterValues() as $value){
                if ($value->getParameter()->getConnectionType()->getId() == 1)
                    $inDataset = $value->getParametervalue();
                if ($value->getParameter()->getConnectionType()->getId() == 3)
                    $params[$value->getParameter()->getSlug()] = $value->getParametervalue();
            }

            if (!$inDataset) continue;

            $params['maxCalcTime'] = $task->getExperiment()->getMaxDuration();
            if (!$params['maxCalcTime']) $params['maxCalcTime'] = 1;

            $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneBy(['datasetId' => $inDataset]);
            if (!$dataset) continue;
            //$params['X'] = $this::url_pre . $dataset->getFilePath();
            $params['X'] = 'http://158.129.140.146/Damis/Data/testData/test.arff'; //FOR TESTING PURPOSES ONLY

            $output->writeln('Task id : ' . $task->getWorkflowtaskid());
            $output->writeln('Wsdl host : ' . $component->getWsdlRunHost());
            $output->writeln('Wsdl function : ' . $component->getWsdlCallFunction());
            $output->writeln('Wsdl function parameters: ' . print_r($params, true));

            //execute
            /* @var $client \SoapClient */
            $client = new \SoapClient($component->getWsdlRunHost());

            var_dump($client->__getTypes());

            $result = array();
            try {
                $result = $client->__soapCall($component->getWsdlCallFunction(), $params);
            } catch (\SoapFault $e) {
                var_dump($e->getMessage(), $e->detail);
            }
            var_dump($result);

            //set to finished
            $task->setWorkflowtaskisrunning(2);//finished
            //$em->flush(); //FOR TESTING PURPOSES ONLY
        }




        //save results file OR save error message
        //set proper out and in if available and successfull


        //find finished experiments and set to finished


        $output->writeln('Executing finished');
    }

}