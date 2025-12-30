<?php

namespace Damis\AlgorithmBundle\Entity\Repository;

use Base\UserBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    /**
     * Finds current user uploaded algorithm files
     *
     * @param User $user
     * @param array                        $orderBy
     * @return Query
     */
    public function getUserAlgorithms($user, $orderBy = ['created' => 'DESC'])
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
