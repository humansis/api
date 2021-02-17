<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use CommonBundle\Repository\LocationRepository;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;

/**
 * Temporary helping service to map new selection criteria structure to old one.
 *
 * After BeneficiaryRepository will be refactorized, this service can be removed.
 */
class FieldDbTransformer
{
    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;

    /** @var VulnerabilityCriterionRepository */
    private $vulnerabilityCriterionRepository;

    /** @var LocationRepository */
    private $locationRepository;

    public function __construct(
        CountrySpecificRepository $countrySpecificRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository,
        LocationRepository $locationRepository
    )
    {
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
        $this->locationRepository = $locationRepository;
    }

    public function toArray(SelectionCriterionInputType $input): array
    {
        if (SelectionCriteriaTarget::BENEFICIARY === $input->getTarget() && ($vulnerability = $this->getVulnerability($input->getField()))) {
            return [
                'condition_string' => $input->getValue() ? '=' : '!=',
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'vulnerabilityCriteria',
                'value_string' => null,
                'weight' => $input->getWeight(),
            ];
        }

        if ((SelectionCriteriaTarget::BENEFICIARY === $input->getTarget() && 'hasNotBeenInDistributionsSince' === $input->getField()) ||
            (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $input->getTarget() && 'disabledHeadOfHousehold' === $input->getField()) ||
            (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'householdSize' === $input->getField())
        ) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && ($countrySpecific = $this->getCountrySpecific($input->getField()))) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'countrySpecific',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => $countrySpecific->getType(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'location' === $input->getField()) {
            /** @var \CommonBundle\Entity\Location $location */
            $location = $this->locationRepository->find($input->getValue());
            if (!$location) {
                throw new EntityNotFoundException();
            }
            $fieldString = '';
            if ($location->getAdm() instanceof \CommonBundle\Entity\Adm1) {
                $fieldString = 'currentAdm1';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm2) {
                $fieldString = 'currentAdm2';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm3) {
                $fieldString = 'currentAdm3';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm4) {
                $fieldString = 'currentAdm4';
            }

            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $fieldString,
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'campName' === $input->getField()) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        return [
            'condition_string' => $input->getCondition(),
            'field_string' => $input->getField(),
            'target' => $input->getTarget(),
            'table_string' => 'Personnal',
            'value_string' => $input->getValue(),
            'weight' => $input->getWeight(),
            'type' => 'table_field',
        ];
    }

    private function getCountrySpecific(string $fieldName): ?CountrySpecific
    {
        static $list = null;
        if (null === $list) {
            $list = [];
            $countrySpecifics = $this->countrySpecificRepository->findBy([]);
            foreach ($countrySpecifics as $countrySpecific) {
                $list[$countrySpecific->getFieldString()] = $countrySpecific;
            }
        }

        return $list[$fieldName] ?? null;
    }

    private function getVulnerability(string $fieldName): ?VulnerabilityCriterion
    {
        static $list = null;
        if (null === $list) {
            $list = [];
            $countrySpecifics = $this->vulnerabilityCriterionRepository->findBy(['active' => true]);
            foreach ($countrySpecifics as $countrySpecific) {
                $list[$countrySpecific->getFieldString()] = $countrySpecific;
            }
        }

        return $list[$fieldName] ?? null;
    }
}