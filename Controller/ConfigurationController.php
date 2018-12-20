<?php

namespace Pintushi\Bundle\ConfigBundle\Controller;

use Pintushi\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pintushi\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Pintushi\Bundle\ConfigBundle\Config\ConfigManager;
use Pintushi\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;

class ConfigurationController extends Controller
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
     *     methods={"GET"},
     *     defaults={
     *         "_api_respond"=true,
     *     }
     * )
     * @AclAncestor("pintushi_config_system")
     *
     * @return array
     */
    public function getConfigMenuTree()
    {
        return $this->systemConfigurationFormProvider->getMenuTree();
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
     *         "_api_normalization_context"={
     *             "groups"= {"read"}
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
