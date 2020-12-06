<?php


namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Model\Vulnerability\CategoryEnum;
use BeneficiaryBundle\Model\Vulnerability\Resolver;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\SelectionCriteria;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use BeneficiaryBundle\Entity\Camp;
use Symfony\Component\Serializer\Serializer;

/**
 * Class CriteriaDistributionService
 * @package DistributionBundle\Utils
 */
class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var Resolver */
    private $resolver;

    /** @var Serializer */
    private $serializer;

    /**
     * CriteriaDistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader    $configurationLoader
     * @param Resolver               $resolver
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader,
        Resolver $resolver,
        Serializer $serializer
    ) {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    /**
     * @param array   $filters
     * @param Project $project
     * @param string  $sector
     * @param string  $subsector
     * @param int     $threshold
     * @param bool    $isCount
     *
     * @return array
     */
    public function load(array $filters, Project $project, string $sector, ?string $subsector, int $threshold, bool $isCount)
    {
        $countryISO3 = $filters['countryIso3'];

        $reachedBeneficiaries = [];

        foreach ($filters['criteria'] as $group) {
            foreach ($group as $index => $criterion) {
                if ($criterion['table_string'] === 'Personnal') {
                    $criterion['type'] = $this->configurationLoader->criteria[$criterion['field_string']]['type'];
                    $group[$index] = $criterion;
                }
            }

            $selectableBeneficiaries = $this->em->getRepository(Beneficiary::class)
                ->getDistributionBeneficiaries($group, $project);

            foreach ($selectableBeneficiaries as $bnf) {
                /** @var Beneficiary $beneficiary */
                $beneficiary = $this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $bnf['id']);

                $protocol = $this->resolver->compute($beneficiary->getHousehold(), $countryISO3, $sector);
                $scores = ['totalScore' => $protocol->getTotalScore()];
                foreach (CategoryEnum::all() as $value) {
                    $scores[$value] = $protocol->getCategoryScore($value);
                }

                if ($protocol->getTotalScore() >= $threshold) {
                    $reachedBeneficiaries[$beneficiary->getId()] = $scores;
                }
            }
        }
        

        if ($isCount) {
            return ['number' =>  count($reachedBeneficiaries)];
        } else {
            // !!!! Those are ids, not directly beneficiaries !!!!
            return ['finalArray' => $reachedBeneficiaries];
        }
    }

    /**
     * @param array   $filters
     * @param Project $project
     * @param int     $threshold
     * @param int     $limit
     * @param int     $offset
     *
     * @return Beneficiary[]
     * @throws \Exception
     */
    public function getList(array $filters, Project $project, int $threshold, int $limit, int $offset)
    {
        $result = $this->load($filters, $project, $filters['sector'], $filters['subsector'], $threshold, false);

        $beneficiaries = $this->em->getRepository(Beneficiary::class)->findBy(['id' => array_keys($result['finalArray'])], null, $limit, $offset);

        $data = [];
        foreach ($beneficiaries as $beneficiary) {
            $serialized = $this->serializer->serialize($beneficiary, 'json', ['groups' => ['SmallHousehold']]);
            $deserialized = json_decode($serialized, true);
            $deserialized['scores'] = $result['finalArray'][$beneficiary->getId()];

            $data[] = $deserialized;
        }

        uasort($data, function ($a, $b) {
            return $b['scores']['totalScore'] <=> $a['scores']['totalScore'];
        });

        return $data;
    }

    /**
     * @param Assistance $assistance
     * @param SelectionCriteria $selectionCriteria
     * @param bool $flush
     * @return SelectionCriteria
     */
    public function save(Assistance $assistance, SelectionCriteria $selectionCriteria, bool $flush)
    {
        $selectionCriteria->setAssistance($assistance);
        $this->em->persist($selectionCriteria);
        if ($flush) {
            $this->em->flush();
        }
        return $selectionCriteria;
    }

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getAll(string $countryISO3)
    {
        $criteria = $this->configurationLoader->load($countryISO3);
        return $criteria;
    }

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getCamps(string $countryISO3)
    {
        $camps = $this->em->getRepository(Camp::class)->findByCountry($countryISO3);
        return $camps;
    }

}
