<?php

namespace BeneficiaryBundle\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
// use Symfony\Component\Serializer\Annotation\ as JMS_Type;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Household
 *
 * @ORM\Table(name="household")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdRepository")
 */
class Household extends AbstractBeneficiary
{
    const ASSETS = [
        0 => 'A/C',
        1 => 'Agricultural Land',
        2 => 'Car',
        3 => 'Flatscreen TV',
        4 => 'Livestock',
        5 => 'Motorbike',
        6 => 'Washing Machine',
    ];

    const SHELTER_STATUSES = [
        0 => 'Values',
        1 => 'Tent',
        2 => 'Makeshift Shelter',
        3 => 'Transitional Shelter',
        4 => 'House/Apartment - Severely Damaged',
        5 => 'House/Apartment - Moderately Damaged',
        6 => 'House/Apartment - Good Condition',
        7 => 'Room or Space in Public Building',
        8 => 'Room or Space in Unfinished Building',
        9 => 'Other',
    ];

    const SUPPORT_RECIEVED_TYPES = [
        0 => 'MPCA',
        1 => 'Cash for Work',
        2 => 'Food Kit',
        3 => 'Food Voucher',
        4 => 'Hygiene Kit',
        5 => 'Shelter Kit',
        6 => 'Shelter Reconstruction Support',
        7 => 'Non Food Items',
        8 => 'Livelihoods Support',
        9 => 'Vocational Training',
        10 => 'None',
        11 => 'Other',
    ];

    /**
     * @var string|null
     *
     * @ORM\Column(name="livelihood", type="enum_livelihood", nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $livelihood;

    /**
     * @var int[]
     *
     * @ORM\Column(name="assets", type="array", nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $assets;

    /**
     * @var int
     *
     * @ORM\Column(name="shelter_status", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $shelterStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullHousehold", "Activity"})
     */
    private $longitude;

    /**
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\CountrySpecificAnswer", mappedBy="household", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold"})
     */
    private $countrySpecificAnswers;

    /**
     * @var Beneficiary
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Beneficiary", mappedBy="household")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $beneficiaries;

    /**
     * @var int|null
     *
     * @ORM\Column(name="incomeLevel", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $incomeLevel;

    /**
     * @var int
     *
     * @ORM\Column(name="foodConsumptionScore", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $foodConsumptionScore;

    /**
     * @var int
     *
     * @ORM\Column(name="copingStrategiesIndex", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $copingStrategiesIndex;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\HouseholdLocation", mappedBy="household", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $householdLocations;

    /**
     * @var int
     *
     * @ORM\Column(name="debt_level", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $debtLevel;

    /**
     * @var int[]
     *
     * @ORM\Column(name="support_received_types", type="array", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $supportReceivedTypes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="support_organization_name", type="string", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $supportOrganizationName;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="support_date_received", type="date", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "Activity"})
     */
    private $supportDateReceived;

    /**
     * @var int|null
     *
     * @ORM\Column(name="income_spent_on_food", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold"})
     */
    private $incomeSpentOnFood;

    /**
     * @var int|null
     *
     * @ORM\Column(name="household_income", type="integer", nullable=true)
     * @SymfonyGroups({"FullHousehold"})
     */
    private $householdIncome;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enumerator_name", type="string", nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $enumeratorName = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="settlement_type", type="enum_settlement_type", nullable=true)
     */
    private $settlementType;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->countrySpecificAnswers = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
        $this->householdLocations = new ArrayCollection();

        $this->assets = [];
        $this->supportReceivedTypes = [];
    }

    /**
     * Set livelihood.
     *
     * @param string|null $livelihood
     *
     * @return self
     */
    public function setLivelihood(?string $livelihood): self
    {
        $this->livelihood = $livelihood;

        return $this;
    }

    /**
     * Get livelihood.
     *
     * @return string|null
     */
    public function getLivelihood(): ?string
    {
        return $this->livelihood;
    }

    /**
     * @return int[]
     */
    public function getAssets(): array
    {
        return (array) $this->assets;
    }

    /**
     * @param int[] $assets
     *
     * @return self
     */
    public function setAssets($assets): self
    {
        foreach ((array) $assets as $asset) {
            if (!isset(self::ASSETS[$asset])) {
                throw new InvalidArgumentException(sprintf('Argument 1 contain invalid asset key %d.', $asset));
            }
        }

        $this->assets = (array) $assets;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getShelterStatus(): ?int
    {
        return $this->shelterStatus;
    }

    /**
     * @param int|null $shelterStatus
     *
     * @return self
     */
    public function setShelterStatus(?int $shelterStatus): self
    {
        if (null !== $shelterStatus && !isset(self::SHELTER_STATUSES[$shelterStatus])) {
            throw new InvalidArgumentException(sprintf('Argument 1 is not valid shelter status key.'));
        }

        $this->shelterStatus = $shelterStatus;

        return $this;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     *
     * @return Household
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set lat.
     *
     * @param string $latitude
     *
     * @return Household
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set long.
     *
     * @param string $longitude
     *
     * @return Household
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get long.
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set beneficiaries.
     *
     * @param Collection|null $collection
     *
     * @return Household
     */
    public function setBeneficiaries(Collection $collection = null)
    {
        $this->beneficiaries = $collection;

        return $this;
    }

    /**
     * Set countrySpecificAnswer.
     *
     * @param Collection|null $collection
     *
     * @return Household
     */
    public function setCountrySpecificAnswers(Collection $collection = null)
    {
        $this->countrySpecificAnswers[] = $collection;

        return $this;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return Household
     */
    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer)
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return Collection
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }

    /**
     * Add beneficiary.
     *
     * @param Beneficiary $beneficiary
     *
     * @return Household
     */
    public function addBeneficiary(Beneficiary $beneficiary)
    {
        $this->beneficiaries->add($beneficiary);

        return $this;
    }

    /**
     * Remove beneficiary.
     *
     * @param Beneficiary $beneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBeneficiary(Beneficiary $beneficiary)
    {
        return $this->beneficiaries->removeElement($beneficiary);
    }

    /**
     * Get beneficiaries.
     *
     * @return Collection
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }

    /**
     * Reset the list of beneficiaries
     */
    public function resetBeneficiaries()
    {
        $this->beneficiaries = new ArrayCollection();

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberDependents(): int
    {
        return count($this->getBeneficiaries()) - 1;
    }

    /**
     * Set incomeLevel.
     *
     * @param int|null $incomeLevel
     *
     * @return Household
     */
    public function setIncomeLevel(?int $incomeLevel)
    {
        $this->incomeLevel = $incomeLevel;

        return $this;
    }

    /**
     * Get incomeLevel.
     *
     * @return int|null
     */
    public function getIncomeLevel(): ?int
    {
        return $this->incomeLevel;
    }

    /**
     * Set foodConsumptionScore.
     *
     * @param int $foodConsumptionScore
     *
     * @return Household
     */
    public function setFoodConsumptionScore($foodConsumptionScore)
    {
        $this->foodConsumptionScore = $foodConsumptionScore;

        return $this;
    }

    /**
     * Get foodConsumptionScore.
     *
     * @return int
     */
    public function getFoodConsumptionScore()
    {
        return $this->foodConsumptionScore;
    }

    /**
     * Set copingStrategiesIndex.
     *
     * @param int $copingStrategiesIndex
     *
     * @return Household
     */
    public function setCopingStrategiesIndex($copingStrategiesIndex)
    {
        $this->copingStrategiesIndex = $copingStrategiesIndex;

        return $this;
    }

    /**
     * Get copingStrategiesIndex.
     *
     * @return int
     */
    public function getCopingStrategiesIndex()
    {
        return $this->copingStrategiesIndex;
    }

    /**
     * Remove householdLocation.
     *
     * @param HouseholdLocation $householdLocation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHouseholdLocation(HouseholdLocation $householdLocation)
    {
        return $this->householdLocations->removeElement($householdLocation);
    }

    /**
     * Add householdLocation.
     *
     * @param HouseholdLocation $householdLocation
     *
     * @return Household
     */
    public function addHouseholdLocation(HouseholdLocation $householdLocation)
    {
        $this->householdLocations[] = $householdLocation;
        $householdLocation->setHousehold($this);
        return $this;
    }

    /**
     * Get householdLocations.
     *
     * @return Collection
     */
    public function getHouseholdLocations()
    {
        return $this->householdLocations;
    }

    /**
     * @return int|null
     */
    public function getDebtLevel(): ?int
    {
        return $this->debtLevel;
    }

    /**
     * @param int|null $debtLevel
     *
     * @return self
     */
    public function setDebtLevel(?int $debtLevel): self
    {
        $this->debtLevel = $debtLevel;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getSupportReceivedTypes(): array
    {
        return (array) $this->supportReceivedTypes;
    }

    /**
     * @param int[] $supportReceivedTypes
     *
     * @return self
     */
    public function setSupportReceivedTypes($supportReceivedTypes): self
    {
        foreach ((array) $supportReceivedTypes as $type) {
            if (!isset(self::SUPPORT_RECIEVED_TYPES[$type])) {
                throw new InvalidArgumentException(sprintf('Argument 1 contain invalid received type key %d.', $type));
            }
        }

        $this->supportReceivedTypes = (array) $supportReceivedTypes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSupportOrganizationName(): ?string
    {
        return $this->supportOrganizationName;
    }

    /**
     * @param string|null $supportOrganizationName
     *
     * @return self
     */
    public function setSupportOrganizationName(?string $supportOrganizationName): self
    {
        $this->supportOrganizationName = $supportOrganizationName;

        return $this;
    }


    /**
     * @return DateTimeInterface|null
     */
    public function getSupportDateReceived(): ?DateTimeInterface
    {
        return $this->supportDateReceived;
    }

    /**
     * @param DateTimeInterface|null $supportDateReceived
     *
     * @return self
     */
    public function setSupportDateReceived(?DateTimeInterface $supportDateReceived): self
    {
        $this->supportDateReceived = $supportDateReceived;

        return $this;
    }

    /**
     * @param int|null $incomeSpentOnFood
     *
     * @return self
     */
    public function setIncomeSpentOnFood(?int $incomeSpentOnFood): self
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;

        return $this;
    }

    /**
     * @param int|null $householdIncome
     *
     * @return self
     */
    public function setHouseholdIncome(?int $householdIncome): self
    {
        $this->householdIncome = $householdIncome;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIncomeSpentOnFood(): ?int
    {
        return $this->incomeSpentOnFood;
    }

    /**
     * @return int|null
     */
    public function getHouseholdIncome(): ?int
    {
        return $this->householdIncome;
    }


    /**
     * @return Beneficiary|null
     */
    public function getHouseholdHead(): ?Beneficiary
    {
        $householdHead = null;
        /** @var Beneficiary $beneficiary */
        foreach ($this->getBeneficiaries() as $beneficiary) {
            if ($beneficiary->isHead()) {
                $householdHead = $beneficiary;
                break;
            }
        }

        return $householdHead;
    }

    /**
     * @return string|null
     */
    public function getEnumeratorName(): ?string
    {
        return $this->enumeratorName;
    }

    /**
     * @param string|null $enumeratorName
     *
     * @return Household
     */
    public function setEnumeratorName(?string $enumeratorName): Household
    {
        $this->enumeratorName = $enumeratorName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSettlementType(): ?string
    {
        return $this->settlementType;
    }

    /**
     * @param string|null $settlementType
     *
     * @return Household
     */
    public function setSettlementType(?string $settlementType): self
    {
        $this->settlementType = $settlementType;

        return $this;
    }
}
