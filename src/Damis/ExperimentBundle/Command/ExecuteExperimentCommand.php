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

            //collect all data

            //find damned component
            $component = $em->getRepository('DamisExperimentBundle:Component')->getTasksComponent($task);
            if (!$component) continue;


            //set to finished
            $task->setWorkflowtaskisrunning(2);//finished
        }



        //execute
        //save results file
        //set proper out and in if available


        //find finished experiments and set to finished


        $output->writeln('Executing finished');
    }

}