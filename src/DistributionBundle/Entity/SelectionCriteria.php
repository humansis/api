<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * SelectionCriteria
 *
 * @ORM\Table(name="selection_criteria")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\SelectionCriteriaRepository")
 */
class SelectionCriteria
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $id;

    /**
     * @var DistributionData
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData", inversedBy="selectionCriteria")
     */
    private $distributionData;

    /**
     * @var string
     *
     * @ORM\Column(name="table_string", type="string", length=255)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $tableString;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=255, nullable=true)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="field_string", type="string", length=255, nullable=true)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $fieldString;

    /**
     * @var int
     *
     * @ORM\Column(name="field_id", type="integer", nullable=true)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $idField;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_string", type="string", length=255, nullable=true)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $conditionString;

    /**
     * @var string
     *
     * @ORM\Column(name="value_string", type="string", length=255, nullable=true)
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $valueString;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer")
     * @Groups({"FullDistribution", "SmallDistribution"})
     */
    private $weight;

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tableString.
     *
     * @param string $tableString
     *
     * @return SelectionCriteria
     */
    public function setTableString($tableString)
    {
        $this->tableString = $tableString;

        return $this;
    }

    /**
     * Get tableString.
     *
     * @return string
     */
    public function getTableString()
    {
        return $this->tableString;
    }

    /**
     * Set fieldString.
     *
     * @param string $fieldString
     *
     * @return SelectionCriteria
     */
    public function setFieldString($fieldString)
    {
        $this->fieldString = $fieldString;

        return $this;
    }

    /**
     * Get fieldString.
     *
     * @return string
     */
    public function getFieldString()
    {
        return $this->fieldString;
    }

    /**
     * Set valueString.
     *
     * @param string $valueString
     *
     * @return SelectionCriteria
     */
    public function setValueString($valueString)
    {
        $this->valueString = $valueString;

        return $this;
    }

    /**
     * Get valueString.
     *
     * @return string
     */
    public function getValueString()
    {
        return $this->valueString;
    }

    /**
     * Set conditionString.
     *
     * @param string $conditionString
     *
     * @return SelectionCriteria
     */
    public function setConditionString($conditionString)
    {
        $this->conditionString = $conditionString;

        return $this;
    }

    /**
     * Get conditionString.
     *
     * @return string
     */
    public function getConditionString()
    {
        return $this->conditionString;
    }

    /**
     * Set target.
     *
     * @param string|null $target
     *
     * @return SelectionCriteria
     */
    public function setTarget($target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set idField.
     *
     * @param int|null $idField
     *
     * @return SelectionCriteria
     */
    public function setIdField($idField = null)
    {
        $this->idField = $idField;

        return $this;
    }

    /**
     * Get idField.
     *
     * @return int|null
     */
    public function getIdField()
    {
        return $this->idField;
    }

    /**
     * Set distributionData.
     *
     * @param \DistributionBundle\Entity\DistributionData|null $distributionData
     *
     * @return SelectionCriteria
     */
    public function setDistributionData(\DistributionBundle\Entity\DistributionData $distributionData = null)
    {
        $this->distributionData = $distributionData;

        return $this;
    }

    /**
     * Get distributionData.
     *
     * @return \DistributionBundle\Entity\DistributionData|null
     */
    public function getDistributionData()
    {
        return $this->distributionData;
    }
}
