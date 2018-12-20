<?php

namespace Pintushi\Bundle\ConfigBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class Config
{
    /**
     * @var int
     *
     */
    protected $id;

    /**
     * @var string
     */
    protected $scopedEntity;

    /**
     * @var int
     */
    protected $recordId;

    /**
     * @var ConfigValue[]
     *
     */
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity
     *
     * @return string
     */
    public function getScopedEntity()
    {
        return $this->scopedEntity;
    }

    /**
     * Set entity
     *
     * @param  string $entity
     *
     * @return Config
     */
    public function setScopedEntity($entity)
    {
        $this->scopedEntity = $entity;

        return $this;
    }

    /**
     * Get record id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set record id
     *
     * @param  integer $recordId
     *
     * @return Config
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Returns array of entity settings
     *
     * @return ArrayCollection|ConfigValue[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param Collection|ConfigValue[] $values
     *
     * @return $this
     */
    public function setValues($values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Return value object related to this config found by given criteria, or creates new one otherwise
     *
     * @param string $section
     * @param string $name
     *
     * @return ConfigValue
     */
    public function getOrCreateValue($section, $name)
    {
        $values = $this->getValues()->filter(
            function (ConfigValue $item) use ($name, $section) {
                return $item->getName() == $name && $item->getSection() == $section;
            }
        );

        /** @var ArrayCollection $values */
        if ($values->first() === false) {
            $value = new ConfigValue();
            $value->setConfig($this);
            $value->setName($name);
            $value->setSection($section);
        } else {
            $value = $values->first();
        }

        return $value;
    }

    public function getValue($section, $name)
    {
        $values = $this->getValues()->filter(
            function (ConfigValue $item) use ($name, $section) {
                return $item->getName() == $name && $item->getSection() == $section;
            }
        );

        /** @var ArrayCollection $values */
        if ($values->first() !== false) {
            return $values->first();
        }

        return null;
    }

    public function removeValue($section, $name)
    {
        /** @var ArrayCollection $values */
        $values = $this->getValues()->filter(
            function (ConfigValue $item) use ($name, $section) {
                return $item->getName() == $name && $item->getSection() == $section;
            }
        );

        if ($values->first() !== false) {
            $value = $values->first();
            $this->getValues()->removeElement($value);
        }
    }
}
