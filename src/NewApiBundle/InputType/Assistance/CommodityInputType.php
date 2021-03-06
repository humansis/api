<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CommodityInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string") // todo change to enum
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $modalityType;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $unit;

    /**
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $value;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="511")
     */
    private $description;

    /**
     * @return string
     */
    public function getModalityType()
    {
        return $this->modalityType;
    }

    /**
     * @param string $modalityType
     */
    public function setModalityType($modalityType)
    {
        $this->modalityType = $modalityType;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
