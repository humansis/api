<?php

namespace CommonBundle\DataFixtures;

use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use CommonBundle\Entity\Adm1;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Utils\DistributionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private $distributionService;

    private $kernel;

    public function __construct(Kernel $kernel, DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidationException
     */
    public function load(ObjectManager $manager)
    {
        $this->loadDistribution($manager);
        $this->loadDistributionWithBankTransfer($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
            LocationFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['test'];
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws ValidationException
     */
    private function loadDistribution(ObjectManager $manager)
    {
        $requestBody = $distributionArray = [
            'adm1' => '',
            'adm2' => '',
            'adm3' => '',
            'adm4' => '',
            'type' => Assistance::TYPE_BENEFICIARY,
            'commodities' => [
                0 => [
                    'modality' => 'CTP',
                    'modality_type' => [
                        'id' => 1,
                    ],
                    'type' => 'Mobile',
                    'unit' => 'USD',
                    'value' => 45,
                    'description' => null,
                ],
            ],
            'date_distribution' => '13-09-2020',
            'location' => [
                'adm1' => 1,
                'adm2' => 1,
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => 'KHM',
            ],
            'location_name' => '',
            'name' => 'Battambang-9/13/2018',
            'project' => [
                'donors' => [],
                'donors_name' => [],
                'id' => '?',
                'name' => '',
                'sectors' => [],
                'sectors_name' => [],
            ],
            'selection_criteria' => [
                0 => [
                    0 => [
                        'condition_string' => 'true',
                        'field_string' => 'disabled',
                        'id_field' => 1,
                        'target' => 'Beneficiary',
                        'table_string' => 'vulnerabilityCriteria',
                        'weight' => 1,
                    ],
                ],
            ],
            'target_type' => Assistance::TYPE_BENEFICIARY,
            'assistance_type' => AssistanceTypeEnum::DISTRIBUTION,
            'threshold' => 1,
        ];

        $this->distributionService->create('KHM', $requestBody, 1);
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws ValidationException
     */
    private function loadDistributionWithBankTransfer(ObjectManager $manager)
    {
        $requestBody = [
            'name' => 'Distribution on money via bank transfer',
            'adm1' => '',
            'adm2' => '',
            'adm3' => '',
            'adm4' => '',
            'type' => Assistance::TYPE_BENEFICIARY,
            'commodities' => [
                0 => [
                    'modality' => 'CTP',
                    'modality_type' => [
                        'id' => $manager->getRepository(ModalityType::class)->findOneBy(['name' => 'Manual Bank Transfer'])->getId(),
                    ],
                    'type' => 'Mobile',
                    'unit' => 'USD',
                    'value' => '45',
                    'description' => null,
                ],
            ],
            'date_distribution' => '13-09-2020',
            'location' => [
                'adm1' => $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'UKR'])->getId(),
                'adm2' => null,
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => 'UKR',
            ],
            'location_name' => '',
            'project' => [
                'donors' => [],
                'donors_name' => [],
                'id' => $manager->getRepository(Project::class)->findOneBy(['iso3' => 'UKR'])->getId(),
                'name' => '',
                'sectors' => [],
                'sectors_name' => [],
            ],
            'selection_criteria' => [
                0 => [
                    0 => [
                        'condition_string' => 'true',
                        'field_string' => 'disabled',
                        'id_field' => 1,
                        'target' => 'Beneficiary',
                        'table_string' => 'vulnerabilityCriteria',
                        'weight' => 1,
                    ],
                ],
            ],
            'target_type' => 'Individual',
            'assistance_type' => AssistanceTypeEnum::DISTRIBUTION,
            'threshold' => '1',
        ];

        $this->distributionService->create('UKR', $requestBody, 1);
    }
}
