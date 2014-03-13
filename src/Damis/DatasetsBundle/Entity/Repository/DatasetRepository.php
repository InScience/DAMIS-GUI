<?php

namespace Damis\DatasetsBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;


class DatasetRepository extends EntityRepository
{
    public function getUserDatasets($user){

        $query = $this->createQueryBuilder('d')
            ->where('d.userId = :user')
            ->setParameter('user', $user)
            ->addOrderBy('d.datasetCreated', 'DESC');

        return $query->getQuery();
    }
}
