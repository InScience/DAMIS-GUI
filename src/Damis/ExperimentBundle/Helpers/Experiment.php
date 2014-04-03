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

    /**
     * Service init with entity manager
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Getting parameters with slugs
     *
     * @param array $parameterIds
     * @return array|null|object
     */
    public function getParameters(array $parameterIds) {
        $repository = $this->em->getRepository('DamisExperimentBundle:Parameter');

        if(count($parameterIds) == 0)
            return [];
        if(count($parameterIds) == 1)
            return $repository->findOneBy(['id' => $parameterIds]);
        if(count($parameterIds) > 1)
            return $repository->findBy(['id' => $parameterIds]);
    }


}