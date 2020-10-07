<?php

namespace CommonBundle\DataFixtures;

use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ModalityFixtures extends Fixture
{
    private $data = [
        [
            'Cash', [
                'Mobile Money',
                'Cash',
                'Smartcard',
                'Manual Bank Transfer',
                // 'Electronic Bank Transfer',
                // 'Digital Wallet',
            ],
        ],
        [
            'Voucher', [
                // 'E-Voucher',
                'QR Code Voucher',
                'Paper Voucher',
            ],
        ],
        [
            'In Kind', [
                'Food',
                'RTE Kit',
                'Bread',
                'Agricultural Kit',
                'WASH Kit',
                'Shelter tool kit',
                'Hygiene kit',
                'Dignity kit',
                'Other NFI',
            ],
        ],
        [
            'Other', [
                'Loan',
                'Business Grant',
            ],
        ],
    ];

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $instance = $manager->getRepository(Modality::class)->findOneByName($datum[0]);
            if (null === $instance) {
                $instance = new Modality();
                $instance->setName($datum[0]);

                $manager->persist($instance);
                $manager->flush();
            }
        }

        foreach ($this->data as $datum) {
            $instance = $manager->getRepository(Modality::class)->findOneByName($datum[0]);
            foreach ($datum[1] as $item) {
                $instance2 = $manager->getRepository(ModalityType::class)->findOneBy([
                    'modality' => $instance,
                    'name' => $item,
                ]);
                if (null === $instance2) {
                    $instance2 = (new ModalityType())
                        ->setName($item)
                        ->setModality($instance);

                    $manager->persist($instance2);
                    $manager->flush();
                }
            }
        }
    }
}
