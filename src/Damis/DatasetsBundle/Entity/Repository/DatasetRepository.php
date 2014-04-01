<?php

namespace Damis\DatasetsBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;


class DatasetRepository extends EntityRepository
{
    /**
     * Finds current user uploaded datasets
     *
     * @param \Base\UserBundle\Entity\User $user
     * @param array $orderBy
     * @return \Doctrine\ORM\Query
     */
    public function getUserDatasets($user, $orderBy = array('created' => 'DESC')){

        $query = $this->createQueryBuilder('d')
            ->andWhere('d.userId = :user')
            ->andWhere('d.hidded = false')
            ->andWhere('d.hidden is null or d.hidden = false')
            ->setParameter('user', $user);
        $sortBy = key($orderBy);
        $order = $orderBy[$sortBy];
        if($sortBy == 'title')
            $query
                ->addOrderBy('d.datasetTitle', $order);
        else
            $query
                ->addOrderBy('d.datasetCreated', $order);

        return $query->getQuery();
    }
}
