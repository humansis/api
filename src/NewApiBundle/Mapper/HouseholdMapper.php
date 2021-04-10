<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use NewApiBundle\Serializer\MapperInterface;

class HouseholdMapper implements MapperInterface
{
    /** @var Household */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Household && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Household) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Household::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getLivelihood(): ?string
    {
        return $this->object->getLivelihood();
    }

    /**
     * @return string[]
     */
    public function getAssets(): iterable
    {
        return $this->object->getAssets();
    }

    public function getShelterStatus(): ?int
    {
        return $this->object->getShelterStatus();
    }

    /**
     * @return int[]
     */
    public function getProjectIds(): iterable
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getProjects()->toArray());
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getLongitude(): ?string
    {
        return $this->object->getLongitude();
    }

    public function getLatitude(): ?string
    {
        return $this->object->getLatitude();
    }

    public function getHouseholdHeadId(): int
    {
        foreach ($this->object->getBeneficiaries() as $beneficiary) {
            /** @var Beneficiary $beneficiary */
            if ($beneficiary->isHead()) {
                return $beneficiary->getId();
            }
        }

        throw new \LogicException('Household #'.$this->object->getId().' does not have HH head.');
    }

    public function getCountrySpecificAnswerIds(): iterable
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getCountrySpecificAnswers()->toArray());
    }

    /**
     * @return int[]
     */
    public function getBeneficiaryIds(): iterable
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getBeneficiaries()->toArray());
    }

    public function getIncomeLevel(): ?int
    {
        return $this->object->getIncomeLevel();
    }

    public function getFoodConsumptionScore(): ?int
    {
        return $this->object->getFoodConsumptionScore();
    }

    public function getCopingStrategiesIndex(): ?int
    {
        return $this->object->getCopingStrategiesIndex();
    }

    public function getDebtLevel(): ?int
    {
        return $this->object->getDebtLevel();
    }

    public function getSupportDateReceived(): ?string
    {
        return $this->object->getSupportDateReceived() ? $this->object->getSupportDateReceived()->format('Y-m-d') : null;
    }

    /**
     * @return int[]
     */
    public function getSupportReceivedTypes(): iterable
    {
        return $this->object->getSupportReceivedTypes();
    }

    public function getSupportOrganizationName(): ?string
    {
        return $this->object->getSupportOrganizationName();
    }

    public function getIncomeSpentOnFood(): ?int
    {
        return $this->object->getIncomeSpentOnFood();
    }

    public function getHouseholdIncome(): ?int
    {
        return $this->object->getHouseholdIncome();
    }

    public function getCampAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_CAMP === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getResidenceAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_RESIDENCE === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getTemporarySettlementAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_SETTLEMENT === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getSettlementType(): ?string
    {
        return $this->object->getSettlementType();
    }
}
