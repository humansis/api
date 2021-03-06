<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Country;
use NewApiBundle\Validator\Constraints\DateGreaterThan;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProjectUpdateInputType", "Strict"})
 */
class ProjectUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $name; // todo check unique name

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $internalId;

    /**
     * @Country
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $iso3;

    /**
     * @Assert\Type("string")
     */
    private $notes;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThan(0)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $target;

    /**
     * @Iso8601
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $startDate;

    /**
     * @Iso8601
     * @Assert\NotBlank
     * @Assert\NotNull
     * @DateGreaterThan(propertyPath="startDate", groups={"Strict"})
     */
    private $endDate;

    /**
     * @Assert\Type("array")
     * @Assert\Count(min=1, groups={"Strict"})
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"ProjectBundle\DBAL\SectorEnum", "all"}, strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $sectors = [];

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer")
     *     },
     *     groups={"Strict"}
     * )
     */
    private $donorIds = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * @param string $internalId
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param int $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartDate()
    {
        return new \DateTime($this->startDate);
    }

    /**
     * @param string $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEndDate()
    {
        return new \DateTime($this->endDate);
    }

    /**
     * @param string $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return array|string[]
     */
    public function getSectors()
    {
        return $this->sectors;
    }

    /**
     * @param array $sectors
     */
    public function setSectors($sectors)
    {
        $this->sectors = $sectors;
    }

    /**
     * @return array|int[]
     */
    public function getDonorIds()
    {
        return $this->donorIds;
    }

    /**
     * @param array $donorIds
     */
    public function setDonorIds($donorIds)
    {
        $this->donorIds = $donorIds;
    }
}
