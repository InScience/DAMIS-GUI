<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
*
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ExperimentRepository extends EntityRepository
{
    public function getClosableExperiments($limit)
    {
        $query = $this->createQueryBuilder('e')
            ->select('e')
            ->leftJoin('e.workflowtasks', 'w', 'with', 'w.experiment = e and (w.workflowtaskisrunning = 0 or w.workflowtaskisrunning = 1 or w.workflowtaskisrunning = 3)')
            ->andWhere('e.status = 2')
            ->andWhere('w.workflowtaskid is null')
            ->setMaxResults($limit);

        return $query->getQuery()->getResult();
    }

    public function getClosableErrExperiments($limit)
    {
        $query = $this->createQueryBuilder('e')
            ->select('e')
            ->leftJoin('e.workflowtasks', 'w', 'with', 'w.experiment = e and w.workflowtaskisrunning = 3')
            ->andWhere('e.status = 2')
            ->andWhere('w.workflowtaskid is not null')
            ->setMaxResults($limit);

        return $query->getQuery()->getResult();
    }

	public function getUserExperiments($user)
    {
        $query = $this->createQueryBuilder('e')
            ->select('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('e.id', 'DESC');

        return $query->getQuery();
    }
}
