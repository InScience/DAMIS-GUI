<?php

namespace Damis\AlgorithmBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    /**
     * Finds current user uploaded algorithm files
     *
     * @param \Base\UserBundle\Entity\User $user
     * @param array                        $orderBy
     * @return \Doctrine\ORM\Query
     */
    public function getUserAlgorithms($user, $orderBy = array('created' => 'DESC'))
    {

        $query = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.hidden != true')
            ->andWhere('d.hidden is null or d.hidden = false')
            ->setParameter('user', $user);
        $sortBy = key($orderBy);
        $order = $orderBy[$sortBy];
        if ($sortBy == 'title') {
            $query
                ->addOrderBy('d.fileTitle', $order);
        } else {
            $query
                ->addOrderBy('d.fileCreated', $order);
        }

        return $query->getQuery();
    }
}
