<?php

namespace Pintushi\Bundle\ConfigBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigValue
 */
class ConfigValue
{
    const FIELD_SCALAR_TYPE = 'scalar';
    const FIELD_OBJECT_TYPE = 'object';
    const FIELD_ARRAY_TYPE  = 'array';

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $name;

    /**
     * @var Config
     *
     */
    protected $config;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $section;

    /**
     * @var string
     */
    protected $textValue;

    /**
     * @var string
     */
    protected $objectValue;

    /**
     * @var string
     */
    protected $arrayValue;

    /**
     * @var string
     */
    protected $type = self::FIELD_SCALAR_TYPE;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

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
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set config
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->clearValue();
        switch (true) {
            case is_object($value):
                $this->objectValue = clone $value;
                $this->type        = self::FIELD_OBJECT_TYPE;
                break;
            case is_array($value):
                $this->arrayValue = $value;
                $this->type       = self::FIELD_ARRAY_TYPE;
                break;
            default:
                $this->textValue = $value;
                $this->type      = self::FIELD_SCALAR_TYPE;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch ($this->type) {
            case self::FIELD_ARRAY_TYPE:
                return $this->arrayValue;
                break;
            case self::FIELD_OBJECT_TYPE:
                return $this->objectValue;
                break;
            default:
                return $this->textValue;
        }
    }

    /**
     * @param string $section
     *
     * @return $this
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime
     *
     * @return Config
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * Clear all value types
     *
     * @return void
     */
    public function clearValue()
    {
        $this->objectValue = $this->arrayValue = $this->textValue = null;
    }
}
