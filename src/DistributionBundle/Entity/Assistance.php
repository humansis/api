<?php

namespace DistributionBundle\Entity;

use CommonBundle\Entity\Location;
use CommonBundle\Utils\ExportableInterface;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Select;
use ProjectBundle\Entity\Project;

use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use BeneficiaryBundle\Entity\Household;

/**
 * Assistance
 *
 * @ORM\Table(name="assistance")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\AssistanceRepository")
 */
class Assistance implements ExportableInterface
{
    const TYPE_BENEFICIARY = 1;
    const TYPE_HOUSEHOLD = 0;

    const NAME_HEADER_ID = "ID SYNC";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="assistance_type", type="assistance_type_enum")
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "FullBooklet", "DistributionOverview"})
     */
    private $assistanceType = AssistanceTypeEnum::DISTRIBUTION;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "FullBooklet", "DistributionOverview"})
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="UpdatedOn", type="datetime")
     */
    private $updatedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_distribution", type="date")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     */
    private $dateDistribution;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $location;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="distributions")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $project;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\SelectionCriteria", mappedBy="assistance")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $selectionCriteria;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $archived = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="validated", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $validated = 0;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingDistribution", mappedBy="distribution", cascade={"persist", "remove"})
     **/
    private $reportingDistribution;

    /**
     * @var int
     *
     * @ORM\Column(name="target_type", type="integer")
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     */
    private $targetType;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\Commodity", mappedBy="assistance")
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     */
    private $commodities;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", mappedBy="assistance")
     *
     * @SymfonyGroups({"FullDistribution", "FullProject"})
     */
    private $distributionBeneficiaries;

    /**
     * @var boolean
     *
     * @ORM\Column(name="completed", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     */
    private $completed = 0;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportingDistribution = new \Doctrine\Common\Collections\ArrayCollection();
        $this->selectionCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        $this->distributionBeneficiaries = new \Doctrine\Common\Collections\ArrayCollection();
        $this->commodities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setUpdatedOn(new \DateTime());
    }

    /**
     * Set id.
     *
     * @param $id
     * @return Assistance
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @return string
     */
    public function getAssistanceType(): string
    {
        return $this->assistanceType;
    }

    /**
     * @param string $assistanceType
     *
     * @return Assistance
     */
    public function setAssistanceType(string $assistanceType): self
    {
        $this->assistanceType = $assistanceType;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Assistance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime $updatedOn
     *
     * @return Assistance
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     *
     * @return string
     */
    public function getUpdatedOn(): string
    {
        return $this->updatedOn->format('Y-m-d H:i:s');
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Assistance
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set validated.
     *
     * @param bool $validated
     *
     * @return Assistance
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated.
     *
     * @return bool
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set completed.
     *
     * @param bool $completed
     *
     * @return Assistance
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * Get completed.
     *
     * @return bool
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * Set type.
     *
     * @param int $targetType one of self::TYPE_*
     *
     * @return self
     */
    public function setTargetType(int $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * @deprecated remove after FE edits done
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->targetType;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getTargetType(): int
    {
        return $this->targetType;
    }

    /**
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Assistance
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set project.
     *
     * @param \ProjectBundle\Entity\Project|null $project
     *
     * @return Assistance
     */
    public function setProject(\ProjectBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \ProjectBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Add selectionCriterion.
     *
     * @param \DistributionBundle\Entity\SelectionCriteria $selectionCriterion
     *
     * @return Assistance
     */
    public function addSelectionCriterion(\DistributionBundle\Entity\SelectionCriteria $selectionCriterion)
    {
        if (null === $this->selectionCriteria) {
            $this->selectionCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->selectionCriteria[] = $selectionCriterion;

        return $this;
    }

    /**
     * Remove selectionCriterion.
     *
     * @param \DistributionBundle\Entity\SelectionCriteria $selectionCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSelectionCriterion(\DistributionBundle\Entity\SelectionCriteria $selectionCriterion)
    {
        return $this->selectionCriteria->removeElement($selectionCriterion);
    }

    /**
     * Get selectionCriteria.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }

    /**
     * Add reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return Assistance
     */
    public function addReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        $this->reportingDistribution[] = $reportingDistribution;

        return $this;
    }

    /**
     * Remove reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        return $this->reportingDistribution->removeElement($reportingDistribution);
    }

    /**
     * Get reportingDistribution.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportingDistribution()
    {
        return $this->reportingDistribution;
    }

    /**
     * Add commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return Assistance
     */
    public function addCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        $this->commodities[] = $commodity;

        return $this;
    }

    /**
     * Remove commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        return $this->commodities->removeElement($commodity);
    }

    /**
     * Get commodities.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCommodities()
    {
        return $this->commodities;
    }

    /**
     * Add distributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary
     *
     * @return Assistance
     */
    public function addDistributionBeneficiary(\DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary)
    {
        if (null === $this->distributionBeneficiaries) {
            $this->distributionBeneficiaries = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->distributionBeneficiaries[] = $distributionBeneficiary;

        return $this;
    }

    /**
     * Remove distributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDistributionBeneficiary(\DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary)
    {
        return $this->distributionBeneficiaries->removeElement($distributionBeneficiary);
    }

    /**
     * Get distributionBeneficiaries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDistributionBeneficiaries()
    {
        return $this->distributionBeneficiaries;
    }

    /**
     * Set dateDistribution.
     *
     * @param \DateTime $dateDistribution
     *
     * @return Assistance
     */
    public function setDateDistribution($dateDistribution)
    {
        $this->dateDistribution = $dateDistribution;

        return $this;
    }

    /**
     * Get dateDistribution.
     *
     * @return \DateTime
     */
    public function getDateDistribution()
    {
        return $this->dateDistribution;
    }


    public function getMappedValueForExport(): array
    {
        // récuperer les criteria de selection  depuis l'objet selectioncriteria

        $valueselectioncriteria = [];
        foreach ($this->getSelectionCriteria() as $criterion) {
            // First we split the camelCase field names
            $field = implode(' ', preg_split('/(?=[A-Z])/', $criterion->getFieldString()));
            // Then we replace the = by a :
            $condition = $criterion->getConditionString() === '=' ? ':' : $criterion->getConditionString();
            $value = $criterion->getValueString();

            // Then we make the string coherent
            if ($field === 'livelihood') {
                $value = Household::LIVELIHOOD[$value];
            } elseif ($field === 'camp Name') {
                $field = 'camp Id';
            }

            if ($field === 'gender' || $field === 'head Of Household Gender') {
                $stringCriterion = $field . " " . $condition . ($value === '0' ? ' Female' : ' Male');
            } elseif ($condition === 'true') {
                $stringCriterion = $field;
            } elseif ($condition === 'false') {
                $stringCriterion = 'not ' . $field;
            } else {
                $stringCriterion = $field . " " . $condition . " " . $value;
            }
            array_push($valueselectioncriteria, $stringCriterion);
        }
        $valueselectioncriteria = join(', ', $valueselectioncriteria);

        // récuperer les valeurs des commodities depuis l'objet commodities

        $valuescommodities = [];
        
        foreach ($this->getCommodities() as $commodity) {
            $stringCommodity = $commodity->getModalityType()->getName() . " " . $commodity->getValue() . " " . $commodity->getUnit();
            array_push($valuescommodities, $stringCommodity);
        }
        $valuescommodities = join(',', $valuescommodities);


        //récuperer les valeurs des distributions des beneficiaires depuis l'objet distribution
        // $valuesdistributionbeneficiaries = [];

        // foreach ($this->getDistributionBeneficiaries() as $value) {
        //     array_push($valuesdistributionbeneficiaries, $value->getIdNumber());
        // }
        // $valuesdistributionbeneficiaries = join(',',$valuesdistributionbeneficiaries);

        $percentage = '';
        foreach ($this->getCommodities() as $index => $commodity) {
            $percentage .= $index !== 0 ? ', ' : '';
            if ($this->getValidated()) {
                $percentage .= $this->getPercentageValue($commodity) . '% ' . $commodity->getModalityType()->getName();
            } else {
                $percentage .= '0% ' . $commodity->getModalityType()->getName();
            }
        }
       
        
        $typeString = $this->getTargetType() === self::TYPE_BENEFICIARY ? 'Beneficiaries' : 'Households';

        $adm1 = $this->getLocation()->getAdm1Name();
        $adm2 = $this->getLocation()->getAdm2Name();
        $adm3 = $this->getLocation()->getAdm3Name();
        $adm4 = $this->getLocation()->getAdm4Name();

        return [
            "ID" => $this->getId(),
            "project" => $this->getProject()->getName(),
            "type" => $typeString,
            // "Archived"=> $this->getArchived(),
            "adm1" => $adm1,
            "adm2" =>$adm2,
            "adm3" =>$adm3,
            "adm4" =>$adm4,
            "Name" => $this->getName(),
            "Date of distribution " => $this->getDateDistribution(),
            "Update on " => $this->updatedOn,
            "Selection criteria" =>  $valueselectioncriteria,
            "Commodities " => $valuescommodities,
            "Number of beneficiaries" => count($this->getDistributionBeneficiaries()),
            "Percentage distributed" => $percentage,
            // "Distribution beneficiaries" =>$valuesdistributionbeneficiaries,
        ];
    }

    public function getPercentageValue($commodity)
    {
        $totalCommodityValue = count($this->getDistributionBeneficiaries()) * $commodity->getValue();
        if ($totalCommodityValue <= 0.00001) {
            return 0;
        }

        $amountSent = 0;
        foreach ($this->getDistributionBeneficiaries() as $distributionBeneficiary) {
            $amountSent += $this->getCommoditySentAmountFromBeneficiary($commodity, $distributionBeneficiary);
        }
        $percentage = $amountSent / $totalCommodityValue * 100;
        return round($percentage * 100) / 100;
    }


    public function getCommoditySentAmountFromBeneficiary($commodity, $distributionBeneficiary)
    {
        $modalityType = $this->getCommodities()[0]->getModalityType()->getName();
        if ($modalityType === 'Mobile Money') {
            $numberOfTransactions = count($distributionBeneficiary->getTransactions());
            if (count($distributionBeneficiary->getTransactions()) > 0) {
                $transaction = $distributionBeneficiary->getTransactions()[$numberOfTransactions - 1];
                return ($transaction->getTransactionStatus() === 1 ? $commodity->getValue() : 0);
            } else {
                return 0;
            }
        } elseif ($modalityType === 'QR Code Voucher') {
            $booklets =  $distributionBeneficiary->getBooklets();
            foreach ($booklets as $booklet) {
                if ($booklet->getStatus() === 1 || $booklet->getStatus() === 2) {
                    return $booklet->getTotalValue();
                }
            }
        } else {
            foreach ($this->getCommodities() as $index => $commodityInList) {
                if ($commodityInList->getId() === $commodity->getId()) {
                    $commodityIndex = $index;
                }
            }
            if (!$distributionBeneficiary->getGeneralReliefs()) {
                return 0;
            }
            $correspondingGeneralRelief = $distributionBeneficiary->getGeneralReliefs()[$commodityIndex];
            return ($correspondingGeneralRelief && $correspondingGeneralRelief->getDistributedAt() ? $commodity->getValue() : 0);
        }
    }
}
