<?php

namespace Pintushi\Bundle\ConfigBundle\Config;

use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Pintushi\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Pintushi\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * Organization config scope
 */
class OrganizationScopeManager extends AbstractScopeManager
{
    /** @var TokenStorageInterface */
    protected $securityContext;

    /** @var int */
    protected $scopeId;

    /**
     * Sets the security context
     *
     * @param TokenStorageInterface $securityContext
     */
    public function setSecurityContext(TokenStorageInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'organization';
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        $this->ensureScopeIdInitialized();

        return $this->scopeId;
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeId($scopeId)
    {
        $this->dispatchScopeIdChangeEvent();

        $this->scopeId = $scopeId;
    }

    /**
     * {@inheritdoc}
     */
    protected function isSupportedScopeEntity($entity)
    {
        return $entity instanceof Organization;
    }

    /**
     * @param User $entity
     *
     * {@inheritdoc}
     */
    protected function getScopeEntityIdValue($entity)
    {
        return $entity->getId();
    }

    /**
     * Makes sure that the scope id is set
     */
    protected function ensureScopeIdInitialized()
    {
        if (!$this->scopeId) {
            $scopeId = 0;

            $token = $this->securityContext->getToken();
            if ($token instanceof OrganizationContextTokenInterface) {
                 $organization = $token->getOrganizationContext();
                if ($organization instanceof OrganizationInterface && $organization->getId()) {
                    $scopeId = $organization->getId();
                }
            }

            $this->scopeId = $scopeId;
        }
    }
}
