<?php

namespace BeneficiaryBundle\Entity;

use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Symfony\Component\Validator\Constraints as Assert;
use CommonBundle\Utils\ExportableInterface;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\HouseholdLocation;

/**
 * Beneficiary
 *
 * @ORM\Table(name="beneficiary")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\BeneficiaryRepository")
 */
class Beneficiary implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="enGivenName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBooklet", "FullBeneficiary"})
     */
    private $enGivenName;

    /**
     * @var string
     *
     * @ORM\Column(name="enFamilyName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $enFamilyName;

    /**
     * @var string
     *
     * @ORM\Column(name="localGivenName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBooklet", "FullBeneficiary"})
     * @Assert\NotBlank(message="The local given name is required.")
     */
    private $localGivenName;

    /**
     * @var string
     *
     * @ORM\Column(name="localFamilyName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     * @Assert\NotBlank(message="The local family name is required.")
     */
    private $localFamilyName;

    /**
     * @var int
     *
     * @ORM\Column(name="gender", type="smallint")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     * @Assert\NotBlank(message="The gender is required.")
     */
    private $gender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold"})
     * @Assert\NotBlank(message="The status is required.")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="residency_status", type="string", length=20)
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold", "FullBeneficiary"})
     * @Assert\Regex("/^(refugee|IDP|resident)$/i")
     */
    private $residencyStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfBirth", type="date")
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     * @Assert\NotBlank(message="The date of birth is required.")
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'d-m-Y H:m:i'>")
     * @Groups({"FullHousehold", "FullBeneficiary"})
     */
    private $updatedOn;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Profile", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "FullBeneficiary"})
     */
    private $profile;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household", inversedBy="beneficiaries")
     */
    private $household;

    /**
     * @var VulnerabilityCriterion
     *
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriterion", cascade={"persist"})
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $vulnerabilityCriteria;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Phone", mappedBy="beneficiary", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $phones;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\NationalId", mappedBy="beneficiary", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $nationalIds;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", mappedBy="beneficiary", cascade={"remove"})
     * @Groups({"FullReceivers", "FullBeneficiary"})
     *
     * @var DistributionBeneficiary $distributionBeneficiary
     */
    private $distributionBeneficiary;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Referral", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "SmallHousehold", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $referral;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->vulnerabilityCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nationalIds = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setUpdatedOn(new \DateTime());

        //TODO check if updatedOn everytime
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
     * Set enGivenName.
     *
     * @param string $enGivenName
     *
     * @return Beneficiary
     */
    public function setEnGivenName($enGivenName)
    {
        $this->enGivenName = $enGivenName;

        return $this;
    }

    /**
     * Get enGivenName.
     *
     * @return string
     */
    public function getEnGivenName()
    {
        return $this->enGivenName;
    }

    /**
     * Set enFamilyName.
     *
     * @param string $enFamilyName
     *
     * @return Beneficiary
     */
    public function setEnFamilyName($enFamilyName)
    {
        $this->enFamilyName = $enFamilyName;

        return $this;
    }

    /**
     * Get enFamilyName.
     *
     * @return string
     */
    public function getEnFamilyName()
    {
        return $this->enFamilyName;
    }

    /**
     * Set localGivenName.
     *
     * @param string $localGivenName
     *
     * @return Beneficiary
     */
    public function setLocalGivenName($localGivenName)
    {
        $this->localGivenName = $localGivenName;

        return $this;
    }

    /**
     * Get localGivenName.
     *
     * @return string
     */
    public function getLocalGivenName()
    {
        return $this->localGivenName;
    }

    /**
     * Set localFamilyName.
     *
     * @param string $localFamilyName
     *
     * @return Beneficiary
     */
    public function setLocalFamilyName($localFamilyName)
    {
        $this->localFamilyName = $localFamilyName;

        return $this;
    }

    /**
     * Get localFamilyName.
     *
     * @return string
     */
    public function getLocalFamilyName()
    {
        return $this->localFamilyName;
    }

    /**
     * Set gender.
     *
     * @param int $gender
     *
     * @return Beneficiary
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return int
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Beneficiary
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime|null $updatedOn
     *
     * @return Beneficiary
     */
    public function setUpdatedOn($updatedOn = null)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return \DateTime|null
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set household.
     *
     * @param \BeneficiaryBundle\Entity\Household|null $household
     *
     * @return Beneficiary
     */
    public function setHousehold(\BeneficiaryBundle\Entity\Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return \BeneficiaryBundle\Entity\Household|null
     */
    public function getHousehold()
    {
        return $this->household;
    }

    /**
     * Add vulnerabilityCriterion.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion
     *
     * @return Beneficiary
     */
    public function addVulnerabilityCriterion(\BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion)
    {
        $this->vulnerabilityCriteria[] = $vulnerabilityCriterion;

        return $this;
    }

    /**
     * Remove vulnerabilityCriterion.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeVulnerabilityCriterion(\BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion)
    {
        return $this->vulnerabilityCriteria->removeElement($vulnerabilityCriterion);
    }

    /**
     * Get vulnerabilityCriterion.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVulnerabilityCriteria()
    {
        return $this->vulnerabilityCriteria;
    }

    /**
     * Set VulnerabilityCriterions.
     *
     * @return Beneficiary
     */
    public function setVulnerabilityCriteria(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->vulnerabilityCriteria = $collection;

        return $this;
    }

    /**
     * Add phone.
     *
     * @param \BeneficiaryBundle\Entity\Phone $phone
     *
     * @return Beneficiary
     */
    public function addPhone(\BeneficiaryBundle\Entity\Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param \BeneficiaryBundle\Entity\Phone $phone
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePhone(\BeneficiaryBundle\Entity\Phone $phone)
    {
        return $this->phones->removeElement($phone);
    }

    /**
     * Get phones.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Set phones.
     *
     * @param $collection
     *
     * @return Beneficiary
     */
    public function setPhones(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->phones = $collection;

        return $this;
    }

    /**
     * Set nationalId.
     *
     * @param  $collection
     *
     * @return Beneficiary
     */
    public function setNationalIds(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->nationalIds = $collection;

        return $this;
    }

    /**
     * Add nationalId.
     *
     * @param \BeneficiaryBundle\Entity\NationalId $nationalId
     *
     * @return Beneficiary
     */
    public function addNationalId(\BeneficiaryBundle\Entity\NationalId $nationalId)
    {
        $this->nationalIds[] = $nationalId;

        return $this;
    }

    /**
     * Remove nationalId.
     *
     * @param \BeneficiaryBundle\Entity\NationalId $nationalId
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(\BeneficiaryBundle\Entity\NationalId $nationalId)
    {
        return $this->nationalIds->removeElement($nationalId);
    }

    /**
     * Get nationalIds.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNationalIds()
    {
        return $this->nationalIds;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return Beneficiary
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getResidencyStatus()
    {
        return $this->residencyStatus;
    }

    /**
     * @param string $residencyStatus
     *
     * @return Beneficiary
     */
    public function setResidencyStatus($residencyStatus)
    {
        $this->residencyStatus = $residencyStatus;
        return $this;
    }

    /**
     * Set profile.
     *
     * @param \BeneficiaryBundle\Entity\Profile|null $profile
     *
     * @return Beneficiary
     */
    public function setProfile(\BeneficiaryBundle\Entity\Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return \BeneficiaryBundle\Entity\Profile|null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set referral.
     *
     * @param \BeneficiaryBundle\Entity\Referral|null $referral
     *
     * @return Beneficiary
     */
    public function setReferral(\BeneficiaryBundle\Entity\Referral $referral = null)
    {
        $this->referral = $referral;

        return $this;
    }

    /**
     * Get referral.
     *
     * @return \BeneficiaryBundle\Entity\Referral|null
     */
    public function getReferral()
    {
        return $this->referral;
    }


    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        // Recover the phones of the beneficiary
        $typephones = ["", ""];
        $prefixphones = ["", ""];
        $valuesphones = ["", ""];
        $proxyphones = ["", ""];

        $index = 0;
        foreach ($this->getPhones()->getValues() as $value) {
            $typephones[$index] = $value->getType();
            $prefixphones[$index] = $value->getPrefix();
            $valuesphones[$index] = $value->getNumber();
            $proxyphones[$index] = $value->getProxy();
            $index++;
        }

        // Recover the  criterions from Vulnerability criteria object
        $valuescriteria = [];
        foreach ($this->getVulnerabilityCriteria()->getValues() as $value) {
            array_push($valuescriteria, $value->getFieldString());
        }
        $valuescriteria = join(',', $valuescriteria);

        // Recover nationalID from nationalID object
        $typenationalID = [];
        $valuesnationalID = [];
        foreach ($this->getNationalIds()->getValues() as $value) {
            array_push($typenationalID, $value->getIdType());
            array_push($valuesnationalID, $value->getIdNumber());
        }
        $typenationalID = join(',', $typenationalID);
        $valuesnationalID = join(',', $valuesnationalID);

        //Recover country specifics for the household
        $valueCountrySpecific = [];
        foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
            $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
        }

        if ($this->getGender() == 0) {
            $valueGender = "Female";
        } else {
            $valueGender = "Male";
        }

        $householdLocations = $this->getHousehold()->getHouseholdLocations();
        $currentHouseholdLocation = null;
        foreach ($householdLocations as $householdLocation) {
            if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
                $currentHouseholdLocation = $householdLocation;
            }
        }

        $location = $currentHouseholdLocation->getLocation();

        $adm1 = $location->getAdm1Name();
        $adm2 = $location->getAdm2Name();
        $adm3 = $location->getAdm3Name();
        $adm4 = $location->getAdm4Name();

        $householdFields = $this->getCommonHouseholdExportFields();
        $beneficiaryFields = $this->getCommonBeneficiaryExportFields();

        if ($this->status === true) {
            $finalArray = array_merge(
                ["household ID" => $this->getHousehold()->getId()],
                $householdFields,
                ["adm1" => $adm1,
                    "adm2" => $adm2,
                    "adm3" => $adm3,
                    "adm4" => $adm4]
            );
        } else {
            $finalArray = [
                "household ID" => "",
                "addressStreet" => "",
                "addressNumber" => "",
                "addressPostcode" => "",
                "camp" => "",
                "tent number" => "",
                "livelihood" => "",
                "incomeLevel" => "",
                "foodConsumptionScore" => "",
                "copingStrategiesIndex" => "",
                "notes" => "",
                "latitude" => "",
                "longitude" => "",
                "adm1" => "",
                "adm2" => "",
                "adm3" => "",
                "adm4" => "",
            ];
        }

        $tempBenef = [
            "beneficiary ID" => $this->getId(),
            "localGivenName" => $this->getLocalGivenName(),
            "localFamilyName" => $this->getLocalFamilyName(),
            "enGivenName" => $this->getEnGivenName(),
            "enFamilyName" => $this->getEnFamilyName(),
            "gender" => $valueGender,
            "head" => $this->getStatus() === true ? "true" : "false",
            "residencyStatus" => $this->getResidencyStatus(),
            "dateOfBirth" => $this->getDateOfBirth()->format('d-m-Y'),
            "vulnerabilityCriteria" => $valuescriteria,
            "type phone 1" => $typephones[0],
            "prefix phone 1" => $prefixphones[0],
            "phone 1" => $valuesphones[0],
            "proxy phone 1" => $proxyphones[0],
            "type phone 2" => $typephones[1],
            "prefix phone 2" => $prefixphones[1],
            "phone 2" => $valuesphones[1],
            "proxy phone 2" => $proxyphones[1],
            "ID Type" => $typenationalID,
            "ID Number" => $valuesnationalID,
        ];

        foreach ($valueCountrySpecific as $key => $value) {
            $finalArray[$key] = $value;
        }

        foreach ($tempBenef as $key => $value) {
            $finalArray[$key] = $value;
        }

        return $finalArray;
    }

    public function getCommonBeneficiaryExportFields()
    {
        $gender = '';
        if ($this->getGender() == 0) {
            $gender = 'Female';
        } else {
            $gender = 'Male';
        }

        return [
            "Local Given Name" => $this->getLocalGivenName(),
            "Local Family Name" => $this->getLocalFamilyName(),
            "English Given Name" => $this->getEnGivenName(),
            "English Family Name" => $this->getEnFamilyName(),
            "Gender" => $gender,
            "Date Of Birth" => $this->getDateOfBirth()->format('d-m-Y'),
        ];
    }

    public function getCommonHouseholdExportFields()
    {

        $householdLocations = $this->getHousehold()->getHouseholdLocations();
        $currentHouseholdLocation = null;
        foreach ($householdLocations as $householdLocation) {
            if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
                $currentHouseholdLocation = $householdLocation;
            }
        }

        $camp = null;
        $tentNumber = null;
        $addressNumber = null;
        $addressStreet = null;
        $addressPostcode = null;

        if ($currentHouseholdLocation->getType() === HouseholdLocation::LOCATION_TYPE_CAMP) {
            $camp = $currentHouseholdLocation->getCampAddress()->getCamp()->getName();
            $tentNumber = $currentHouseholdLocation->getCampAddress()->getTentNumber();
        } else {
            $addressNumber = $currentHouseholdLocation->getAddress()->getNumber();
            $addressStreet = $currentHouseholdLocation->getAddress()->getStreet();
            $addressPostcode = $currentHouseholdLocation->getAddress()->getPostcode();
        }

        $livelihood = null;
        if (null !== $this->getHousehold()->getLivelihood()) {
            $livelihood = Household::LIVELIHOOD[$this->getHousehold()->getLivelihood()];
        }

        $assets = array_map(function ($value) {
            return Household::ASSETS[$value];
        }, (array) $this->getHousehold()->getAssets());

        $shelterStatus = null;
        if (null !== $this->getHousehold()->getShelterStatus()) {
            $shelterStatus = Household::SHELTER_STATUSES[$this->getHousehold()->getShelterStatus()];
        }

        $supportReceivedTypes = array_map(function ($value) {
            return Household::SUPPORT_RECIEVED_TYPES[$value];
        }, (array) $this->getHousehold()->getSupportReceivedTypes());

        $supportDateReceived = null;
        if (null !== $this->getHousehold()->getSupportDateReceived()) {
            $supportDateReceived = $this->getHousehold()->getSupportDateReceived()->format("m/d/Y");
        }

        return [
            "addressStreet" => $addressStreet,
            "addressNumber" => $addressNumber,
            "addressPostcode" => $addressPostcode,
            "camp" => $camp,
            "tent number" => $tentNumber,
            "livelihood" => $livelihood,
            "incomeLevel" => $this->getHousehold()->getIncomeLevel(),
            "foodConsumptionScore" => $this->getHousehold()->getFoodConsumptionScore(),
            "copingStrategiesIndex" => $this->getHousehold()->getCopingStrategiesIndex(),
            "notes" => $this->getHousehold()->getNotes(),
            "latitude" => $this->getHousehold()->getLatitude(),
            "longitude" => $this->getHousehold()->getLongitude(),
            "Assets" => implode(', ', $assets),
            "Shelter Status" => $shelterStatus,
            "Debt Level" => $this->getHousehold()->getDebtLevel(),
            "Support Received Types" => implode(', ', $supportReceivedTypes),
            "Support Date Received" => $supportDateReceived,
        ];
    }

    public function getCommonExportFields()
    {

        $referral_type = null;
        $referral_comment = null;
        if ($this->getReferral()) {
            $referral_type = $this->getReferral()->getType();
            $referral_comment = $this->getReferral()->getComment();
        }

        $referralInfo = [
            "Referral Type" => $referral_type ? Referral::REFERRALTYPES[$referral_type] : null,
            "Referral Comment" => $referral_comment
        ];

        return array_merge($this->getCommonHouseholdExportFields(), $this->getCommonBeneficiaryExportFields(), $referralInfo);
    }

    /**
     * Returns age of beneficiary in years
     * @return int|null
     */
    public function getAge(): ?int
    {
        if ($this->getDateOfBirth()) {
            try {
                return $this->getDateOfBirth()->diff(new \DateTime('now'))->y;
            } catch (\Exception $ex) {
                return null;
            }
        }

        return null;
    }
}
