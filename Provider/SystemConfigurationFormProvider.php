<?php

namespace Pintushi\Bundle\ConfigBundle\Provider;

class SystemConfigurationFormProvider extends AbstractProvider
{
    const TREE_NAME = 'system_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuTree()
    {
        return $this->getMenuTreeData(self::TREE_NAME, self::CORRECT_MENU_NESTING_LEVEL);
    }

    /**
     * Use default checkbox label
     *
     * @return string
     */
    protected function getParentCheckboxLabel()
    {
        return 'pintushi.config.system_configuration.use_default';
    }
}
