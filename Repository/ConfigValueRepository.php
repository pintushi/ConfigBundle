<?php

namespace Pintushi\Bundle\ConfigBundle\Repository;

use Pintushi\Bundle\CoreBundle\Doctrine\ORM\ServiceEntityRepository as EntityRepository;
use Pintushi\Bundle\ConfigBundle\Entity\Config;
use Pintushi\Bundle\ConfigBundle\Entity\ConfigValue;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class ConfigValueRepository
 *
 * @package Pintushi\Bundle\ConfigBundle\Repository
 */
class ConfigValueRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigValue::class);
    }

    /**
     * Remove "values" entity depends on it's section and name identifier
     *
     * @param Config $config
     * @param array  $removed [..., ['SECTION_IDENTIFIER', 'NAME_IDENTIFIER'], ...]
     *
     * @return array
     */
    public function removeValues(Config $config, array $removed)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $this->getEntityManager()->beginTransaction();
        foreach ($removed as $item) {
            $builder->delete('Pintushi\Bundle\ConfigBundle\Entity\ConfigValue', 'cv')
                ->where('cv.config = :config')
                ->andWhere('cv.name = :name')
                ->andWhere('cv.section = :section')
                ->setParameter('config', $config)
                ->setParameter('section', $item[0])
                ->setParameter('name', $item[1]);
            $builder->getQuery()->execute();
        }
        $this->getEntityManager()->commit();
    }

    /**
     * @param string $section
     */
    public function removeBySection($section)
    {
        $qb = $this->createQueryBuilder('configValue');
        $qb->delete()
            ->where($qb->expr()->eq('configValue.section', ':section'))
            ->setParameter('section', $section)
            ->getQuery()->execute();
    }

    /**
     * @param string $scope
     * @param string $section
     * @param string $name
     *
     * @return ConfigValue[]
     */
    public function getConfigValues($scope, $section, $name)
    {
        return $this->createQueryBuilder('cv')
            ->join('cv.config', 'c')
            ->where('c.scopedEntity = :entityName')
            ->andWhere('cv.section = :section')
            ->andWhere('cv.name = :name')
            ->setParameters([
                'entityName' => $scope,
                'section' => $section,
                'name' => $name,
            ])
            ->getQuery()
            ->getResult();
    }
}
