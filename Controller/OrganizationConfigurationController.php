<?php

namespace Pintushi\Bundle\ConfigBundle\Controller;

use Pintushi\Bundle\ConfigBundle\Config\ConfigManager;
use Pintushi\Bundle\SecurityBundle\Annotation\Acl;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\ConfigBundle\Config\OrganizationScopeManager;
use Pintushi\Bundle\OrganizationBundle\Provider\OrganizationConfigurationFormProvider;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Pintushi\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrganizationConfigurationController extends Controller
{
    private $configManager;
    private $organizationConfigurationFormProvider;
    private $tokenAccessor;
    private $configHandler;
    private $organizationRepository;

    public function __construct(
        ConfigManager $configManager,
        OrganizationConfigurationFormProvider $organizationConfigurationFormProvider,
        TokenAccessor $tokenAccessor,
        ConfigHandler $configHandler,
        OrganizationRepository $organizationRepository
    ) {
        $this->configManager = $configManager;
        $this->organizationConfigurationFormProvider = $organizationConfigurationFormProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->configHandler = $configHandler;
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * @Route(
     *      path="/organization/{id}/{activeGroup}/{activeSubGroup}",
     *      name="api_organization_config",
     *      methods={"GET","POST"},
     *      requirements={
     *          "id"="\d+"
     *      },
     *      defaults={
     *          "activeGroup"=null,
     *          "activeSubGroup"=null,
     *          "_api_respond"=true,
     *          "_api_normalization_context"={
     *             "groups"= {"read"}
     *         },
     *         "_format"="json"
     *      }
     * )
     * @Acl(
     *      id="pintushi_organization_organization_config",
     *      type="entity",
     *      class="PintushiOrganizationBundle:Organization",
     *      permission="CONFIGURE"
     * )
     *
     * @param Organization $entity
     * @param null $activeGroup
     * @param null $activeSubGroup
     * @return array
     */
    public function organizationConfig($id, $activeGroup = null, $activeSubGroup = null)
    {
        $entity = $this->organizationRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('未找到该租户');
        }
        $result = $this->config($entity, $activeGroup, $activeSubGroup);

        return $result;
    }

    /**
     * @Route(
     *     path="/organization/config/{activeGroup}/{activeSubGroup}",
     *     name="api_organization_profile_configuration",
     *     methods={"GET", "POST"},
     *     defaults={
     *         "_api_respond"=true,
     *         "_api_normalize_request"=false,
     *         "_api_normalization_context"={
     *             "groups"= {"Organization"}
     *         },
     *         "_format"="json"
     *     }
     * )
     * @AclAncestor("pintushi_organization_update_configuration")
     *
     * @param null $activeGroup
     * @param null $activeSubGroup
     *
     * @return array
     */
    public function organizationProfileConfig($activeGroup = null, $activeSubGroup = null)
    {
        $result = $this->config($this->tokenAccessor->getOrganization(), $activeGroup, $activeSubGroup);

        return  $result;
    }

    /**
     * @Route(
     *     path="/organization/config_tree",
     *     name="api_organization_profile_configuration_tree",
     *     methods={"GET"},
     * )
     * @AclAncestor("pintushi_organization_update_configuration")
     *
     * @return array
     */
    public function getConfigMenuTree()
    {
        $tree =  $this->organizationConfigurationFormProvider->getMenuTree();

        return new JsonResponse($tree);
    }

    /**
     * @param Organization $entity
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    protected function config(Organization $entity, $activeGroup = null, $activeSubGroup = null)
    {
        /** @var ConfigManager $manager */
        $manager = $this->configManager;
        $prevScopeId = $manager->getScopeId();

        //update scope id to match currently configured user
        $manager->setScopeIdFromEntity($entity);

        list($activeGroup, $activeSubGroup) = $this->organizationConfigurationFormProvider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $form = false;

        $configValues = [];

        if ($activeSubGroup !== null) {
            $form = $this->organizationConfigurationFormProvider->getForm($activeSubGroup);

            $this->configHandler
                ->setConfigManager($manager)
                ->process($form, $this->get('request_stack')->getCurrentRequest());

            $configValues = $this->configManager->getSettingsByForm($form);
        }

        //revert previous scope id
        $manager->setScopeId($prevScopeId);

        return [
            'entity'         => $entity,
            'configValues'   => $configValues,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
            'scopeInfo'      => $manager->getScopeInfo()
        ];
    }
}
