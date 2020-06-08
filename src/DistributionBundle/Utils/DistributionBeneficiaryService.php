<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DistributionBundle\Entity\DistributionData;
use BeneficiaryBundle\Entity\ProjectBeneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use DateTime;

/**
 * Class DistributionBeneficiaryService
 * @package DistributionBundle\Utils
 */
class DistributionBeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;


    /**
     * DistributionBeneficiaryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->container = $container;
    }
    
    /**
     * Get all beneficiaries from a distribution
     *
     * @param DistributionData $distributionData
     * @return array
     */
    public function getBeneficiaries(DistributionData $distributionData)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getAllofDistribution($distributionData);
        return $beneficiaries;
    }
    
    /**
     * Get all distribution beneficiaries from a distribution
     *
     * @param DistributionData $distributionData
     * @return array
     */
    public function getDistributionBeneficiaries(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['distributionData' => $distributionData]);
        return $distributionBeneficiaries;
    }

    /**
     * Get distribution beneficiaries without booklets
     *
     * @param DistributionData $distributionData
     * @return array
     */
    public function getDistributionAssignableBeneficiaries(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findAssignable($distributionData);
        return $distributionBeneficiaries;
    }


    /**
     * Get random beneficiaries from a distribution
     *
     * @param DistributionData $distributionData
     * @param Int $numberRandomBeneficiary
     * @return array
     */
    public function getRandomBeneficiaries(DistributionData $distributionData, Int $numberRandomBeneficiary)
    {
        $listReceivers = $this->em->getRepository(Beneficiary::class)->getNotRemovedofDistribution($distributionData);

        if (sizeof($listReceivers) < $numberRandomBeneficiary) {
            return $listReceivers;
        }


        $randomIds = array_rand($listReceivers, $numberRandomBeneficiary);

        if (gettype($randomIds) == 'integer') {
            return [$listReceivers[$randomIds]];
        }

        $randomReceivers = array();
        foreach ($randomIds as $id) {
            array_push($randomReceivers, $listReceivers[$id]);
        }

        return $randomReceivers;
    }

    /**
     * Add either a beneficiary of a household(in this case, we assigned the head of the household) to a distribution
     *
     * @param DistributionData $distributionData
     * @param array $beneficiariesArray
     * @return DistributionBeneficiary
     * @throws \Exception
     */
    public function addBeneficiary(DistributionData $distributionData, array $beneficiariesData)
    {
        $beneficiary = null;

        $beneficiariesArray = $beneficiariesData['beneficiaries'];

        $distributionBeneficiaries = [];

        if ($beneficiariesArray && sizeof($beneficiariesArray) > 0) {
            foreach ($beneficiariesArray as $beneficiaryArray) {

                if ($beneficiaryArray !== $beneficiariesData["__country"]) {
                    switch ($distributionData->getType()) {
                        case 0:
                            $headHousehold = $this->em->getRepository(Beneficiary::class)->find($beneficiaryArray["id"]);
                            $household = $headHousehold->getHousehold();
                            if (!$household instanceof Household) {
                                throw new \Exception("This household was not found.");
                            }
                            $beneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                            break;
                        case 1:
                            $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryArray["id"]);
                            break;
                        default:
                            throw new \Exception("The type of the distribution is undefined.");
                    }
                    
                    $distributionBeneficiary = new DistributionBeneficiary();

                    $sameDistributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)
                        ->findOneBy(['beneficiary' => $beneficiary, 'distributionData' => $distributionData]);
                    // $beneficiariesArray contains at least the country so a unique beneficiary would be a size of 2
                    if ($sameDistributionBeneficiary && sizeof($beneficiariesArray) <= 2 && !$sameDistributionBeneficiary->getRemoved()) {
                        throw new \Exception('This beneficiary/household is already part of the distribution', Response::HTTP_BAD_REQUEST);
                    } else if ($sameDistributionBeneficiary && sizeof($beneficiariesArray) <= 2 && $sameDistributionBeneficiary->getRemoved()) {
                        $sameDistributionBeneficiary->setRemoved(0)
                            ->setJustification($beneficiariesData['justification']);
                        $this->em->persist($sameDistributionBeneficiary);
                    } else if (!$sameDistributionBeneficiary) {
                        $distributionBeneficiary->setDistributionData($distributionData)
                            ->setBeneficiary($beneficiary)
                            ->setRemoved(0)
                            ->setJustification($beneficiariesData['justification']);
                        $this->em->persist($distributionBeneficiary);
                        array_push($distributionBeneficiaries, $distributionBeneficiary);
                    }
                }
            }
            if ($distributionData->getValidated()) {
                $distributionData = $this->container->get('distribution.distribution_service')->setCommoditiesToNewBeneficiaries($distributionData, $distributionBeneficiaries);
            }

            $distributionData->setUpdatedOn(new \DateTime());
            $this->em->persist($distributionData);

            $this->em->flush();
        } else {
            return null;
        }
        return $distributionBeneficiary;
    }

    /**
     * @param DistributionData $distributionData
     * @param Beneficiary $beneficiary
     * @return bool
     */
    public function removeBeneficiaryInDistribution(DistributionData $distributionData, Beneficiary $beneficiary, $deletionData)
    {
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => $beneficiary->getId(), 'distributionData' => $distributionData->getId()]);

        // Update updatedOn datetime
        $distributionData->setUpdatedOn(new DateTime());

        $distributionBeneficiary->setRemoved(1)
            ->setJustification($deletionData['justification']);
        $this->em->persist($distributionBeneficiary);
        $this->em->flush();
        return true;
    }

    /**
     * @param array $objectBeneficiary
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(array $objectBeneficiary, string $type)
    {
        $beneficiaries = array();
        foreach ($objectBeneficiary as $value) {
            $gender = '';

            if ($value['gender'] === '0') {
                $gender = 'Female';
            } else {
                $gender = 'Male';
            }

            array_push($beneficiaries, [
                "English given name" => $value['en_given_name'],
                "English family name"=> $value['en_family_name'],
                "Local given name" => $value['local_given_name'],
                "Local family name"=> $value['local_family_name'],
                "Gender" => $gender,
                "Status" => $value['status'],
                "Residency status" => $value['residency_status'],
                "Date of birth" => $value['date_of_birth']
            ]);
        }
        return $this->container->get('export_csv_service')->export($beneficiaries, 'distributions', $type);
    }

    /**
     * Get all beneficiaries in a selected project
     *
     * @param Project $project
     * @param string $target
     * @return array
     */
    public function getAllBeneficiariesInProject(Project $project, string $target)
    {
        return $this->em->getRepository(Beneficiary::class)->getAllOfProject($project->getId(), $target);
    }
}
