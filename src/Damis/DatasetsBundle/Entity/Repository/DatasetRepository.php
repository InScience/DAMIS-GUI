<?php

namespace Damis\DatasetsBundle\Entity\Repository;

use Base\UserBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

class DatasetRepository extends EntityRepository
{
    /**
    * Finds current user uploaded datasets
    *
    * @param User $user
    * @param array                        $orderBy
    * @return Query
    */
   public function getUserDatasets($user, $orderBy = ['created' => 'DESC'])
    {
        $query = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.hidden != true')
            ->andWhere('d.hidden is null or d.hidden = false')
            ->setParameter('user', $user);
        
        if (empty($orderBy)) {
            $orderBy = ['created' => 'DESC'];
        }
        $sortBy = array_key_first($orderBy);
        
        if ($sortBy === null || !isset($orderBy[$sortBy])) {
            $sortBy = 'created';
            $order = 'DESC';
        } else {
            $order = $orderBy[$sortBy];
        }
        
        if ($sortBy == 'title') {
            $query->addOrderBy('d.datasetTitle', $order);
        } else {
            $query->addOrderBy('d.datasetCreated', $order);
        }

        return $query->getQuery();
    }
}
