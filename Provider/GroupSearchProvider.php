<?php

namespace Pintushi\Bundle\ConfigBundle\Provider;

use Pintushi\Bundle\ConfigBundle\Config\ConfigBag;
use Pintushi\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

class GroupSearchProvider implements SearchProviderInterface
{
    /** @var ConfigBag */
    private $configBag;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ConfigBag $configBag
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigBag $configBag, TranslatorInterface $translator)
    {
        $this->configBag = $configBag;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->configBag->getGroupsNode($name) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        $group = $this->configBag->getGroupsNode($name);
        if ($group === false) {
            throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
        }

        $searchData = [];
        if (isset($group['title'])) {
            $searchData[] = $this->translator->trans($group['title']);
        }

        return $searchData;
    }
}
