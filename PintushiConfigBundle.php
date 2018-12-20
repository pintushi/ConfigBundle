<?php

namespace Pintushi\Bundle\ConfigBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Pintushi\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Pintushi\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationSearchPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PintushiConfigBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SystemConfigurationPass());
        $container->addCompilerPass(new SystemConfigurationSearchPass());
    }
}
