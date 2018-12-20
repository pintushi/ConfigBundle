<?php

namespace Pintushi\Bundle\ConfigBundle\Controller;

use Pintushi\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pintushi\Bundle\ConfigBundle\Config\ConfigSectionManager;

class ConfigSectionController extends Controller
{
    private $configSectionManager;

    public function __construct(
        ConfigSectionManager $configSectionManager
    ) {
        $this->configSectionManager = $configSectionManager;
    }

    /**
     * Get the list of all configuration sections
     *
     * @Route(
     *     name="api_admin_configuration_sections",
     *     path="/configurations",
     *     methods={"GET"},
     *     defaults={
     *        "_api_respond"=true,
     *        "_api_normalization_context"={
     *             "groups"= {"read"}
     *      }
     *  }
     * )
     *
     * @AclAncestor("pintushi_config_system")
     *
     * @return Response
     */
    public function sections()
    {
        $data = $this->configSectionManager->getSections();

        return $data;
    }

    /**
     * Get all configuration data of the specified section
     *
     * @param Request $request
     * @param string $path The configuration section path. For example: look-and-feel/grid
     *
     * @Route(
     *      name="api_admin_configuration_view",
     *      path="/configurations/{path}",
     *      methods={"GET"},
     *      requirements={"path"="[\w-]+[\w-\/]*"},
     *      defaults={
     *          "_api_respond"=true,
     *          "_api_normalization_context"={
     *             "groups"= {"read"}
     *          }
     *      }
     * )
     *
     * @AclAncestor("pintushi_config_system")
     *
     * @return Response
     */
    public function view(Request $request, $path)
    {
        $data = $this->configSectionManager->getData($path, $request->get('scope', 'user'));

        return $data;
    }
}
