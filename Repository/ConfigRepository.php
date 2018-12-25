<?php

namespace Pintushi\Bundle\ConfigBundle\Repository;

use Videni\Bundle\RestBundle\Doctrine\ORM\ServiceEntityRepository as EntityRepository;
use Pintushi\Bundle\ConfigBundle\Entity\Config;
use Doctrine\Common\Persistence\ManagerRegistry;

class ConfigRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param string $scope
     * @param mixed  $scopeId
     *
     * @return Config|null
     */
    public function findByEntity($scope, $scopeId)
    {
        return $this->createQueryBuilder('c')
            ->select('c, cv')
            ->leftJoin('c.values', 'cv')
            ->where('c.scopedEntity = :entityName AND c.recordId = :entityId')
            ->setParameter('entityName', $scope)
            ->setParameter('entityId', $scopeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $scope
     * @param integer $scopeId
     * @return mixed
     */
    public function deleteByEntity($scope, $scopeId)
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.scopedEntity = :entityName AND c.recordId = :entityId')
            ->setParameter('entityName', $scope)
            ->setParameter('entityId', $scopeId)
            ->getQuery()
            ->execute();
    }
}
