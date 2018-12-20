<?php

namespace Pintushi\Bundle\ConfigBundle\Provider;

use Pintushi\Bundle\ConfigBundle\Config\Section\SectionDefinition;
use Pintushi\Bundle\ConfigBundle\Config\Section\VariableDefinition;
use Pintushi\Bundle\ConfigBundle\Config\ConfigBag;
use Pintushi\Bundle\ConfigBundle\Config\ConfigManager;
use Pintushi\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Pintushi\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Pintushi\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Pintushi\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Pintushi\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Pintushi\Bundle\ConfigBundle\Form\Type\FormType;
use Pintushi\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractProvider implements ProviderInterface
{
    const CORRECT_FIELDS_NESTING_LEVEL = 5;
    const CORRECT_MENU_NESTING_LEVEL = 3;

    /** @var ConfigBag */
    protected $configBag;

    /** @var array */
    protected $processedTrees = [];

    /** @var array */
    protected $processedMenuTrees = [];

    /** @var array */
    protected $processedSubTrees = [];

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FormFactoryInterface */
    protected $factory;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var  ChainSearchProvider */
    protected $searchProvider;

    /** @var FormRegistryInterface  */
    protected $formRegistry;

    /**
     * @param ConfigBag $configBag
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $factory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ChainSearchProvider $searchProvider
     * @param FormRegistryInterface $formRegistry
     */
    public function __construct(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $factory,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FormRegistryInterface $formRegistry
    ) {
        $this->configBag = $configBag;
        $this->translator = $translator;
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
        $this->searchProvider = $searchProvider;
        $this->formRegistry = $formRegistry;
    }

    /**
     * Use default checkbox label
     *
     * @return string
     */
    abstract protected function getParentCheckboxLabel();

    /**
     * {@inheritdoc}
     */
    public function getSectionTree($path = null)
    {
        $sections = empty($path) ? [] : explode('/', $path);
        array_unshift($sections, ProcessorDecorator::SECTION_TREE_ROOT);

        $tree = $this->configBag->getConfig();
        $rootSectionName = null;
        foreach ($sections as $section) {
            if (!isset($tree[$section])) {
                throw new ItemNotFoundException(sprintf('Config API section "%s" is not defined.', $path));
            }
            $tree            = & $tree[$section];
            $rootSectionName = $section;
        }

        return $this->buildSectionTree(
            $rootSectionName === ProcessorDecorator::SECTION_TREE_ROOT ? '' : $rootSectionName,
            $tree
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtree($subtreeRootName)
    {
        if (!isset($this->processedSubTrees[$subtreeRootName])) {
            $treeData = $this->getTree();
            $subtree  = TreeUtils::findNodeByName($treeData, $subtreeRootName);

            if ($subtree === null) {
                throw new ItemNotFoundException(sprintf('Subtree "%s" not found', $subtreeRootName));
            }

            $this->processedSubTrees[$subtreeRootName] = $subtree;
        }

        return $this->processedSubTrees[$subtreeRootName];
    }

    /**
     * @param string $treeName
     * @param int    $correctFieldsLevel
     *
     * @throws ItemNotFoundException
     * @return GroupNodeDefinition
     */
    protected function getTreeData($treeName, $correctFieldsLevel)
    {
        if (!isset($this->processedTrees[$treeName])) {
            $treeRoot = $this->configBag->getTreeRoot($treeName);
            if ($treeRoot === false) {
                throw new ItemNotFoundException(sprintf('Tree "%s" is not defined.', $treeName));
            }

            $definition = $treeRoot;
            $data = $this->buildGroupNode($definition, $correctFieldsLevel);
            $tree = new GroupNodeDefinition($treeName, $definition, $data);
            $this->processedTrees[$tree->getName()] = $tree;
        }

        return $this->processedTrees[$treeName];
    }

    /**
     * @param string $treeName
     * @param int $correctMenuLevel
     *
     * @throws ItemNotFoundException
     * @return array
     */
    protected function getMenuTreeData($treeName, $correctMenuLevel)
    {
        if (!isset($this->processedMenuTrees[$treeName])) {
            $treeRoot = $this->configBag->getTreeRoot($treeName);
            if ($treeRoot === false) {
                throw new ItemNotFoundException(sprintf('Tree "%s" is not defined.', $treeName));
            }

            $this->processedMenuTrees[$treeName] = $this->buildMenuTree($treeRoot, $correctMenuLevel);
        }

        return $this->processedMenuTrees[$treeName];
    }

    /**
     * Builds group node, called recursively
     *
     * @param array $nodes
     * @param int   $correctFieldsLevel fields should be placed on the same levels that comes from view
     * @param int   $level              current level
     *
     * @throws ItemNotFoundException
     * @throws \Exception
     * @return array
     */
    protected function buildGroupNode($nodes, $correctFieldsLevel, $level = 0)
    {
        $level++;
        foreach ($nodes as $name => $node) {
            if (is_array($node) && isset($node['children'])) {
                $group = $this->configBag->getGroupsNode($name);
                if ($group === false) {
                    throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
                }

                $data  = $this->buildGroupNode($node['children'], $correctFieldsLevel, $level);
                $node  = new GroupNodeDefinition($name, array_merge($group, $nodes[$name]), $data);
                $node->setLevel($level);

                $nodes[$node->getName()] = $node;
            } else {
                if ($level !== $correctFieldsLevel) {
                    throw new \Exception(
                        sprintf('Field "%s" will not be ever rendered. Please check nesting level', $node)
                    );
                }
                $nodes[$name] = $this->buildFieldNode($node);
            }
        }

        return $nodes;
    }

    /**
     * @param array $nodes
     * @param int $correctMenuLevel
     * @param int $level
     * @param string $parentName
     * @param array $groupedData
     * @return array
     */
    protected function buildMenuTree($nodes, $correctMenuLevel, $level = 0, $parentName = '#', array $groupedData = [])
    {
        $nodes = $this->sortChildrenByPriority($nodes);

        $level++;
        foreach ($nodes as $name => $node) {
            if (is_array($node) && isset($node['children']) && $correctMenuLevel > $level) {
                $groupedData = $this->buildMenuTree(
                    $node['children'],
                    $correctMenuLevel,
                    $level,
                    $name,
                    $groupedData
                );

                $groupedData[] = $this->buildMenuTreeItem($name, $parentName, $node);
            } else {
                $groupedData[] = $this->buildMenuTreeItemWithSearchData($name, $parentName, $node);
            }
        }

        return $groupedData;
    }

    /**
     * @param string $name
     * @param string $parentName
     * @param array $node
     * @return array
     */
    public function buildMenuTreeItem($name, $parentName, array $node)
    {
        $group = $this->configBag->getGroupsNode($name);

        if ($group === false) {
            throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
        }
        $text = isset($group['title']) ? $this->translator->trans($group['title']) : null;

        return [
            'id' => $name,
            'text' => $text,
            'icon' => $group['icon'] ?? null,
            'parent' => $parentName,
            'priority' => $node['priority'] ?? 0,
            'search_by' => [$text]
        ];
    }

    /**
     * @param string $name
     * @param string $parentName
     * @param array $node
     * @return array
     */
    private function buildMenuTreeItemWithSearchData($name, $parentName, array $node)
    {
        $jsTreeData = $this->buildMenuTreeItem($name, $parentName, $node);

        if ($this->searchProvider->supports($name)) {
            $children = array_key_exists('children', $node) ? $node['children'] : [];
            $jsTreeData['search_by'] = $this->prepareSearchData($name, $children);
        }

        return $jsTreeData;
    }

    /**
     * @param string $name
     * @param array $itemChildren
     * @return array
     */
    private function prepareSearchData($name, array $itemChildren = [])
    {
        $itemSearchData = $this->searchProvider->getData($name);

        foreach ($itemChildren as $childName => $childData) {
            if (is_array($childData)) {
                $children = array_key_exists('children', $childData) ? $childData['children'] : [];
                $itemSearchData = array_merge($itemSearchData, $this->prepareSearchData($childName, $children));
            } else {
                $itemSearchData = array_merge($itemSearchData, $this->prepareSearchData($childData));
            }
        }

        return $itemSearchData;
    }

    /**
     * Builds field data by name
     *
     * @param string $node field node name
     *
     * @return FieldNodeDefinition
     * @throws ItemNotFoundException
     */
    protected function buildFieldNode($node)
    {
        $fieldsRoot = $this->configBag->getFieldsRoot($node);
        if ($fieldsRoot === false) {
            throw new ItemNotFoundException(sprintf('Field "%s" is not defined.', $node));
        }

        return new FieldNodeDefinition($node, $fieldsRoot);
    }

    /**
     * @param string $sectionName
     * @param array  $tree
     *
     * @return SectionDefinition
     */
    protected function buildSectionTree($sectionName, array $tree)
    {
        $section = new SectionDefinition($sectionName);
        foreach ($tree as $key => $val) {
            if ($key === 'section') {
                continue;
            }
            if (!empty($val['section'])) {
                $section->addSubSection(
                    $this->buildSectionTree($key, $val)
                );
            } else {
                $section->addVariable(
                    new VariableDefinition($key, $val['type'])
                );
            }
        }

        return $section;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTransformer($key)
    {
        return $this->configBag->getDataTransformer($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm($group)
    {
        $block = $this->getSubtree($group);

        $toAdd = [];
        $blockConfig = $block->toBlockConfig();

        if (!$block->isEmpty()) {
            $sbc = [];

            /** @var $subblock GroupNodeDefinition */
            foreach ($block as $subblock) {
                $sbc += $subblock->toBlockConfig();
                if (!$subblock->isEmpty()) {
                    /** @var $field FieldNodeDefinition */
                    foreach ($subblock as $field) {
                        $field->replaceOption('block', $block->getName())
                            ->replaceOption('subblock', $subblock->getName());

                        $toAdd[] = $field;
                    }
                }
            }

            $blockConfig[$block->getName()]['subblocks'] = $sbc;
        }

        $formBuilder = $this
            ->factory
            ->createNamedBuilder(
                '',
                FormType::class,
                null,
                [
                'csrf_protection' => false,
                'block_config' => $blockConfig
                ]
            );
        foreach ($toAdd as $field) {
            $this->addFieldToForm($formBuilder, $field);
        }

        return $formBuilder->getForm();
    }

    /**
     * {@inheritdoc}
     */
    public function chooseActiveGroups($activeGroup, $activeSubGroup)
    {
        $tree = $this->getTree();

        if ($activeGroup === null) {
            $activeGroup = TreeUtils::getFirstNodeName($tree);
        }

        // we can find active subgroup only in case if group is specified
        if ($activeSubGroup === null && $activeGroup) {
            $subtree = TreeUtils::findNodeByName($tree, $activeGroup);

            if ($subtree instanceof GroupNodeDefinition) {
                $subGroups = TreeUtils::getByNestingLevel($subtree, 2);

                if ($subGroups instanceof GroupNodeDefinition) {
                    $activeSubGroup = TreeUtils::getFirstNodeName($subGroups);
                }
            }
        }

        return [$activeGroup, $activeSubGroup];
    }

    /**
     * Checks whether an access to a given ACL resource is granted
     *
     * @param string $resourceName
     *
     * @return bool
     */
    protected function checkIsGranted($resourceName)
    {
        return $this->authorizationChecker->isGranted($resourceName);
    }

    /**
     * @param FormBuilderInterface $form
     * @param FieldNodeDefinition  $fieldDefinition
     */
    protected function addFieldToForm(FormBuilderInterface $form, FieldNodeDefinition $fieldDefinition)
    {
        if ($fieldDefinition->getAclResource() && !$this->checkIsGranted($fieldDefinition->getAclResource())) {
            // field is not allowed to be shown, do nothing
            return;
        }

        $name = str_replace(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            ConfigManager::SECTION_VIEW_SEPARATOR,
            $fieldDefinition->getPropertyPath()
        );

        // take config field options form field definition
        $configFieldOptions = array_intersect_key(
            $fieldDefinition->getOptions(),
            array_flip(['label', 'required', 'block', 'subblock', 'tooltip', 'resettable'])
        );
        // pass only options needed to "value" form type
        $fieldFormType = $fieldDefinition->getType();
        $configFieldOptions['target_field_type'] = $fieldFormType;
        $configFieldOptions['target_field_alias'] = $this->formRegistry->getType($fieldFormType)->getBlockPrefix();
        $configFieldOptions['target_field_options'] = array_diff_key(
            $fieldDefinition->getOptions(),
            $configFieldOptions
        );

        if ($fieldDefinition->needsPageReload()) {
            $configFieldOptions['target_field_options']['attr']['data-needs-page-reload'] = '';
            $configFieldOptions['use_parent_field_options']['attr']['data-needs-page-reload'] = '';
        }
        $configFieldOptions['parent_checkbox_label'] = $this->getParentCheckboxLabel();

        $form->add($name, FormFieldType::class, $configFieldOptions);
    }

    /**
     * @param array $children
     * @return array
     */
    private function sortChildrenByPriority(array $children)
    {
        uasort($children, function ($childA, $childB) {
            $priorityA = $childA['priority'] ?? 0;
            $priorityB = $childB['priority'] ?? 0;

            return $priorityB <=> $priorityA;
        });

        return $children;
    }
}
