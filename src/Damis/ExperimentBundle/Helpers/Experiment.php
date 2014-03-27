<?php
/**
 * Created by PhpStorm.
 * User: Karolis
 * Date: 14.3.27
 * Time: 16:21
 */

namespace Damis\ExperimentBundle\Helpers;

use Doctrine\ORM\EntityManager;

class Experiment {

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getParameters(array $parameterIds) {
        $repository = $this->em->getRepository('DamisExperimentBundle:Parameter');

        if(count($parameterIds) == 1)
            return $repository->findOneBy(['id' => $parameterIds]);
    }


}