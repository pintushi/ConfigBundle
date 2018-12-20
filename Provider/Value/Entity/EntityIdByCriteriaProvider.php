<?php

namespace Pintushi\Bundle\ConfigBundle\Provider\Value\Entity;

use Doctrine\ORM\EntityRepository;
use Pintushi\Bundle\ConfigBundle\Provider\Value\ValueProviderInterface;
use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;

class EntityIdByCriteriaProvider implements ValueProviderInterface
{
    /**
     * @var EntityRepository|DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $defaultEntityCriteria;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $entityClass
     * @param array          $defaultEntityCriteria
     */
    public function __construct(DoctrineHelper $doctrineHelper, string $entityClass, array $defaultEntityCriteria)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClass = $entityClass;
        $this->defaultEntityCriteria = $defaultEntityCriteria;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        $entity = $this->doctrineHelper->getEntityRepositoryForClass($this->entityClass)
            ->findOneBy($this->defaultEntityCriteria);

        if (!$entity) {
            return null;
        }

        return $entity->getId();
    }
}
