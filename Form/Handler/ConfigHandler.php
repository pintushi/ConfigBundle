<?php

namespace Pintushi\Bundle\ConfigBundle\Form\Handler;

use Pintushi\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Videni\Bundle\RestBundle\Validator\Exception\ValidationException;

class ConfigHandler
{
    /**
     * @var ConfigManager
     */
    protected $manager;

    /**
     * @param ConfigManager $manager
     */
    public function __construct(ConfigManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param ConfigManager $manager
     *
     * @return $this
     */
    public function setConfigManager(ConfigManager $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->manager;
    }

    /**
     * Process form
     *
     * @param FormInterface $form
     *
     * @param Request $request
     * @return bool True on successful processing, false otherwise
     */
    public function process(FormInterface $form, Request $request)
    {
        $settingsData = $this->manager->getSettingsByForm($form);
        $form->setData($settingsData);

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $changeSet = $this->manager->save($form->getData());
                $handler = $form->getConfig()->getAttribute('handler');
                if (null !== $handler && is_callable($handler)) {
                    call_user_func($handler, $this->manager, $changeSet, $form);
                }

                return true;
            }

            $violations = new ConstraintViolationList();
            foreach ($form->getErrors(true) as $error) {
                $violations->add($error->getCause());
            }

            if (0 !== \count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return false;
    }
}
