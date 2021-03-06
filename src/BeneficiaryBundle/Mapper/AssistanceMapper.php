<?php

namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;

class AssistanceMapper
{
    const TARGET_TYPE_TO_TYPE_MAPPING = [
        AssistanceTargetType::INDIVIDUAL => 1,
        AssistanceTargetType::HOUSEHOLD => 0,
        AssistanceTargetType::COMMUNITY => 2,
        AssistanceTargetType::INSTITUTION => 3,
    ];

    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;

    /** @var AssistanceBeneficiaryRepository */
    private $distributionBNFRepo;

    /**
     * AssistanceMapper constructor.
     *
     * @param BeneficiaryMapper                 $beneficiaryMapper
     * @param AssistanceBeneficiaryRepository $distributionBNFRepo
     */
    public function __construct(
        BeneficiaryMapper $beneficiaryMapper,
        AssistanceBeneficiaryRepository $distributionBNFRepo
    ) {
        $this->beneficiaryMapper = $beneficiaryMapper;
        $this->distributionBNFRepo = $distributionBNFRepo;
    }

    public function toMinimalArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
        ];
    }

    public function toMinimalArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toMinimalArray($assistance);
        }
    }

    public function toBeneficiaryOnlyArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        /** @var AbstractBeneficiary[] $bnfs */
        $bnfs = $assistance->getDistributionBeneficiaries()->map(
            function (AssistanceBeneficiary $db) {
                return $db->getBeneficiary();
            }
        );
        $dbs = [];
        foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $dbs[] = [
                'beneficiary' => $this->beneficiaryMapper->toMinimalArrays($bnfs),
            ];
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'beneficiaries' => $this->beneficiaryMapper->toMinimalArrays($bnfs),
            'distribution_beneficiaries' => $dbs,
        ];
    }

    public function toBeneficiaryOnlyArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toBeneficiaryOnlyArray($assistance);
        }
    }

    public function toFullArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        $assistanceArray = [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'updated_on' => $assistance->getUpdatedOnDateTime()->format('d-m-Y H:i'),
            'date_distribution' => $assistance->getDateDistribution(),
            'location' => $assistance->getLocation(),
            'project' => $assistance->getProject(),
            'selection_criteria' => $this->transformSelectionCriteria($assistance->getSelectionCriteria()),
            'archived' => $assistance->getArchived(),
            'validated' => $assistance->getValidated(),
            'reporting_distribution' => $assistance->getReportingAssistance(),
            'type' => self::TARGET_TYPE_TO_TYPE_MAPPING[$assistance->getTargetType()] ?? null,
            'assistance_type' => $assistance->getAssistanceType(),
            'target_type' => $assistance->getTargetType(),
            'commodities' => $assistance->getCommodities(),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
            'sector' => $assistance->getSector(),
            'subsector' => $assistance->getSubSector(),
            'description' => $assistance->getDescription(),
            'households_targeted' => $assistance->getHouseholdsTargeted(),
            'individuals_targeted' => $assistance->getIndividualsTargeted(),
        ];

        return $assistanceArray;
    }

    public function toFullArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toFullArray($assistance);
        }
    }

    /**
     * @param Assistance|null $assistance
     *
     * @return array
     *
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        /** @var AbstractBeneficiary[] $bnfs */
        $bnfs = [];
        foreach ($assistance->getDistributionBeneficiaries() as $db) {
            if ($db->getBeneficiary() instanceof Beneficiary
                && !$db->getRemoved()
                && !$db->getBeneficiary()->getArchived()
            ) {
                $bnfs[] = $db->getBeneficiary();
            }
        }

        $assistanceArray = [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'updated_on' => $assistance->getUpdatedOnDateTime()->format('d-m-Y H:i'),
            'date_distribution' => $assistance->getDateDistribution(),
            'location' => $assistance->getLocation(),
            'project' => $assistance->getProject(),
            'selection_criteria' => $assistance->getSelectionCriteria(),
            'archived' => $assistance->getArchived(),
            'validated' => $assistance->getValidated(),
            'reporting_distribution' => $assistance->getReportingAssistance(),
            'type' => AssistanceTargetType::INDIVIDUAL === $assistance->getTargetType() ? 1 : 0,
            'assistance_type' => $assistance->getAssistanceType(),
            'target_type' => $assistance->getTargetType(),
            'commodities' => $assistance->getCommodities(),
            'distribution_beneficiaries' => $this->beneficiaryMapper->toOldMobileArrays($bnfs),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
            'description' => $assistance->getDescription(),
            'households_targeted' => $assistance->getHouseholdsTargeted(),
            'individuals_targeted' => $assistance->getIndividualsTargeted(),
        ];

        return $assistanceArray;
    }

    /**
     * @param iterable $assistances
     *
     * @return iterable
     *
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toOldMobileArray($assistance);
        }
    }

    /**
     * @param SelectionCriteria[] $criteria
     */
    private function transformSelectionCriteria(iterable $criteria)
    {
        $result = [];

        foreach ($criteria as $criterion) {
            $result[][] = $criterion;
        }

        return $result;
    }
}
