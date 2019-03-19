<?php

namespace Pintushi\Bundle\ConfigBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pintushi\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Pintushi\Bundle\ConfigBundle\Config\ConfigManager;
use Pintushi\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Pintushi\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Component\HttpFoundation\JsonResponse;

class SystemConfigurationController extends Controller
{
    private $configManager;
    private $configHandler;
    private $systemConfigurationFormProvider;

    public function __construct(
        ConfigManager $configManager,
        SystemConfigurationFormProvider $systemConfigurationFormProvider,
        ConfigHandler $configHandler
    ) {
        $this->configManager = $configManager;
        $this->systemConfigurationFormProvider = $systemConfigurationFormProvider;
        $this->configHandler = $configHandler;
    }

    /**
     * @Route(
     *     path="/system/config_tree",
     *     name="api_system_configuration_tree",
     *     methods={"GET"}
     * )
     * @AclAncestor("pintushi_config_system")
     *
     * @return array
     */
    public function getConfigMenuTree()
    {
        $tree = $this->systemConfigurationFormProvider->getMenuTree();

        return new JsonResponse($tree);
    }

    /**
     * @Route(
     *    path="/system/{activeGroup}/{activeSubGroup}",
     *    name="api_config_configuration_system",
     *    methods={"POST", "GET"},
     *    defaults={
     *         "activeGroup" = null,
     *         "activeSubGroup" = null,
     *         "_api_respond"=true,
     *         "_format"="json",
     *          "_api_normalize_request"=false,
     *         "_api_normalization_context"={
     *             "groups"= {"Default"}
     *         }
     *    }
     * )
     * @Acl(
     *      id="pintushi_config_system",
     *      type="action",
     *      label="pintushi.config.acl.action.general.label",
     *      group_name="",
     *      category="application"
     * )
     * @param Request $request
     * @param mixed $activeGroup
     * @param mixed $activeSubGroup
     *
     * @return array
     */
    public function system(Request $request, $activeGroup = null, $activeSubGroup = null)
    {
        list($activeGroup, $activeSubGroup) = $this->systemConfigurationFormProvider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $form = false;
        $configValues = [];

        if ($activeSubGroup !== null) {
            $form = $this->systemConfigurationFormProvider->getForm($activeSubGroup);

            $this->configHandler->process($form, $request);

            $configValues = $this->configManager->getSettingsByForm($form);
        }

        return [
            'configValues' => $configValues,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup
        ];
    }
}
