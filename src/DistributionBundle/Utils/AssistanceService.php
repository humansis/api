<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Model\Vulnerability\CategoryEnum;
use CommonBundle\Utils\LocationService;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\GeneralReliefItemUpdateInputType;
use NewApiBundle\InputType\GeneralReliefPatchInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Voucher;

/**
 * Class AssistanceService
 * @package DistributionBundle\Utils
 */
class AssistanceService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var CommodityService $commodityService */
    private $commodityService;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var CriteriaAssistanceService $criteriaAssistanceService */
    private $criteriaAssistanceService;

    /** @var AbstractRetriever $retriever */
    private $retriever;

    /** @var ContainerInterface $container */
    private $container;

    /** @var FieldDbTransformer */
    private $fieldDbTransformer;

    /**
     * AssistanceService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param LocationService $locationService
     * @param CommodityService $commodityService
     * @param ConfigurationLoader $configurationLoader
     * @param CriteriaAssistanceService $criteriaAssistanceService
     * @param FieldDbTransformer $fieldDbTransformer
     * @param string $classRetrieverString
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        ValidatorInterface $validator,
        LocationService $locationService,
        CommodityService $commodityService,
        ConfigurationLoader $configurationLoader,
        CriteriaAssistanceService $criteriaAssistanceService,
        FieldDbTransformer $fieldDbTransformer,
        string $classRetrieverString,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->configurationLoader = $configurationLoader;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->fieldDbTransformer = $fieldDbTransformer;
        $this->container = $container;
        try {
            $class = new \ReflectionClass($classRetrieverString);
            $this->retriever = $class->newInstanceArgs([$this->em]);
        } catch (\Exception $exception) {
            throw new \Exception("Your class Retriever is undefined or malformed.", 0, $exception);
        }
    }


    /**
     * @param Assistance $assistance
     * @return Assistance
     * @throws \Exception
     */
    public function validateDistribution(Assistance $assistance)
    {
        try {
            $assistance->setValidated(true)
                ->setUpdatedOn(new \DateTime());
            $beneficiaries = $assistance->getDistributionBeneficiaries();
            return $this->setCommoditiesToNewBeneficiaries($assistance, $beneficiaries);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Assistance $assistance
     */
    public function unvalidateDistribution(Assistance $assistance): void
    {
        if ($this->isDistributionStarted($assistance)) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance is already started.');
        }

        $assistance
            ->setValidated(false)
            ->setUpdatedOn(null);

        foreach ($assistance->getDistributionBeneficiaries() as $distributionBeneficiary) {
            /** @var AssistanceBeneficiary $distributionBeneficiary */
            foreach ($distributionBeneficiary->getGeneralReliefs() as $gri) {
                $this->em->remove($gri);
            }
        }

        $this->em->persist($assistance);
        $this->em->flush();
    }

    /**
     * @param Assistance $assistance
     * @param $beneficiaries
     * @return Assistance
     * @throws \Exception
     */
    public function setCommoditiesToNewBeneficiaries(Assistance $assistance, $beneficiaries) {
        $commodities = $assistance->getCommodities();
        foreach ($commodities as $commodity) {
            if ($commodity->getModalityType()->isGeneralRelief()) {
                foreach ($beneficiaries as $beneficiary) {
                    $generalRelief = new GeneralReliefItem();
                    $generalRelief->setAssistanceBeneficiary($beneficiary);
                    $this->em->persist($generalRelief);
                }
            }
        }
        $this->em->flush();

        return $assistance;
    }

    public function findByCriteria(AssistanceCreateInputType $inputType, Pagination $pagination)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new \Doctrine\ORM\EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }

        $filters = $this->mapping($inputType);
        $filters['criteria'] = $filters['selection_criteria'];

        $result = $this->criteriaAssistanceService->load($filters, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false);
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $beneficiaries = $this->em->getRepository(\BeneficiaryBundle\Entity\AbstractBeneficiary::class)->findBy(['id' => $ids]);

        return new \CommonBundle\Pagination\Paginator($beneficiaries, $count);
    }

    /**
     * @param AssistanceCreateInputType $inputType
     * @param Pagination                $pagination
     *
     * @return \CommonBundle\Pagination\Paginator|\DistributionBundle\DTO\VulnerabilityScore[]
     * @throws EntityNotFoundException
     * @throws \BeneficiaryBundle\Exception\CsvParserException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function findVulnerabilityScores(AssistanceCreateInputType $inputType, Pagination $pagination)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new \Doctrine\ORM\EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }

        $filters = $this->mapping($inputType);
        $filters['criteria'] = $filters['selection_criteria'];

        $result = $this->criteriaAssistanceService->load($filters, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false);
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $list = [];
        foreach ($ids as $id) {
            $beneficiary = $this->em->getRepository(\BeneficiaryBundle\Entity\AbstractBeneficiary::class)->find($id);
            $list[] = new \DistributionBundle\DTO\VulnerabilityScore($beneficiary, $result['finalArray'][$id]);
        }

        return new \CommonBundle\Pagination\Paginator($list, $count);
    }

    public function create(AssistanceCreateInputType $inputType)
    {
        $distributionArray = $this->mapping($inputType);

        $result = $this->createFromArray($inputType->getIso3(), $distributionArray);

        return $result['distribution'];
    }

    /**
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @return array
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function createFromArray($countryISO3, array $distributionArray)
    {
        $location = $distributionArray['location'];
        unset($distributionArray['location']);

        $selectionCriteriaGroup = $distributionArray['selection_criteria'] ?? null;
        unset($distributionArray['selection_criteria']);

        $sector = $distributionArray['sector'];
        unset($distributionArray['sector']);

        $subsector = $distributionArray['subsector'] ?? null;
        unset($distributionArray['subsector']);

        if (isset($distributionArray['assistance_type']) && AssistanceType::ACTIVITY === $distributionArray['assistance_type']) {
            unset($distributionArray['commodities']);

            // ignore user defined commodities and create some generic instead
            $modalityType = $this->em->getRepository(ModalityType::class)->findOneBy(['name' => 'Activity item']);
            $distributionArray['commodities'][] = ['value' => 1, 'unit' => 'activity', 'description' => null, 'modality_type' => ['id' => $modalityType->getId()]];
        }

        /** @var Assistance $distribution */
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), Assistance::class, 'json', [
            \Symfony\Component\Serializer\Normalizer\PropertyNormalizer::DISABLE_TYPE_ENFORCEMENT => true
        ]);
        $distribution->setUpdatedOn(new \DateTime());
        $errors = $this->validator->validate($distribution);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $distribution->setTargetType($distributionArray['target_type']);

        $location = $this->locationService->getLocation($countryISO3, $location);
        $distribution->setLocation($location);
        $distribution->setName(self::generateName($location, $distribution->getDateDistribution()));

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        if ($projectTmp instanceof Project) {
            $distribution->setProject($projectTmp);
        }

        $distribution->setSector($sector);
        $distribution->setSubSector($subsector);

        foreach ($distribution->getCommodities() as $item) {
            $distribution->removeCommodity($item);
        }
        foreach ($distributionArray['commodities'] as $item) {
            $distribution->addCommodity($this->commodityService->create($distribution, $item, false));
        }

        $listReceivers = [];
        if (AssistanceTargetType::COMMUNITY === $distribution->getTargetType()) {
            foreach ($distributionArray['communities'] as $id) {
                $community = $this->container->get('doctrine')->getRepository(Community::class)->find($id);
                $assistanceBeneficiary = (new AssistanceBeneficiary())
                    ->setAssistance($distribution)
                    ->setBeneficiary($community)
                    ->setRemoved(0);

                $this->em->persist($assistanceBeneficiary);
                $listReceivers[] = $community->getId();
            }
        } elseif (AssistanceTargetType::INSTITUTION === $distribution->getTargetType()) {
            foreach ($distributionArray['institutions'] as $id) {
                $institution = $this->container->get('doctrine')->getRepository(Institution::class)->find($id);
                $assistanceBeneficiary = (new AssistanceBeneficiary())
                    ->setAssistance($distribution)
                    ->setBeneficiary($institution)
                    ->setRemoved(0);
                $this->em->persist($assistanceBeneficiary);

                $listReceivers[] = $institution->getId();
            }
        } else {
            $criteria = [];
            foreach ($selectionCriteriaGroup as $i => $criteriaData) {
                foreach ($criteriaData as $j => $criterionArray) {
                    /** @var SelectionCriteria $criterion */
                    $criterion = $this->serializer->deserialize(json_encode($criterionArray), SelectionCriteria::class, 'json', [
                        \Symfony\Component\Serializer\Normalizer\PropertyNormalizer::DISABLE_TYPE_ENFORCEMENT => true
                    ]);
                    $criterion->setGroupNumber($i);
                    $this->criteriaAssistanceService->save($distribution, $criterion, false);
                    $criteria[$i][$j] = $criterionArray;
                }
            }

            $distributionArray['selection_criteria'] = $criteria;
            $listReceivers = $this->guessBeneficiaries($distributionArray, $countryISO3, $projectTmp, $distribution->getTargetType(), $sector, $subsector, $distributionArray['threshold']);
            $this->saveReceivers($distribution, $listReceivers);
        }

        $this->em->persist($distribution);
        $this->em->flush();

        return ["distribution" => $distribution, "data" => $listReceivers];
    }

    /**
     * @param array   $criteria
     * @param         $countryISO3
     * @param Project $project
     * @param string  $targetType
     * @param         $sector
     * @param         $subsector
     * @param int     $threshold
     *
     * @return mixed
     */
    public function guessBeneficiaries(array $criteria, $countryISO3, Project $project, string $targetType, $sector, $subsector, int $threshold)
    {
        $criteria['criteria'] = $criteria['selection_criteria'];
        $criteria['countryIso3'] = $countryISO3;

        return $this->container->get('distribution.criteria_assistance_service')->load($criteria, $project, $targetType, $sector, $subsector, $threshold, false);
    }

    /**
     * @param Assistance $assistance
     * @param array      $listReceivers
     *
     * @throws \Exception
     */
    public function saveReceivers(Assistance $assistance, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver => $scores) {
            /** @var Beneficiary $beneficiary */
            $beneficiary = $this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $receiver);

            $assistanceBeneficiary = (new AssistanceBeneficiary())
                ->setAssistance($assistance)
                ->setBeneficiary($beneficiary)
                ->setRemoved(0)
                ->setVulnerabilityScores(json_encode($scores));

            $this->em->persist($assistanceBeneficiary);
        }
    }

    /**
     * Get all distributions
     *
     * @return array
     */
    public function findAll(string $country)
    {
        $distributions = [];
        $projects = $this->em->getRepository(Project::class)->findAll();
        
        foreach ($projects as $proj) {
            if ($proj->getIso3() == $country) {
                foreach ($proj->getDistributions() as $distrib) {
                    array_push($distributions, $distrib);
                }
            }
        }

        return $distributions;
    }


    /**
     * Get all distributions
     *
     * @param int $id
     * @return null|object
     */
    public function findOneById(int $id)
    {
        return $this->em->getRepository(Assistance::class)->findOneBy(['id' => $id]);
    }

    /**
     * @param Assistance $assistance
     * @return null|object|string
     */
    public function archived(Assistance $assistance)
    {
        if (!empty($assistance)) {
            $assistance->setArchived(1);
        }

        $this->em->persist($assistance);
        $this->em->flush();

        return "Archived";
    }

    /**
     * @param Assistance $assistance
     * @return null|object|string
     */
    public function complete(Assistance $assistance)
    {
        if (!empty($assistance)) {
                $assistance->setCompleted()
                                ->setUpdatedOn(new \DateTime);         
        }

        $this->em->persist($assistance);
        $this->em->flush();

        return "Completed";
    }

    /**
     * Edit a distribution
     *
     * @param Assistance $assistance
     * @param array $distributionArray
     * @return Assistance
     * @throws \Exception
     */
    public function edit(Assistance $assistance, array $distributionArray)
    {
        $assistance->setDateDistribution(\DateTime::createFromFormat('d-m-Y', $distributionArray['date_distribution']))
            ->setUpdatedOn(new \DateTime());
        $distributionNameWithoutDate = explode('-', $assistance->getName())[0];
        $newDistributionName = $distributionNameWithoutDate . '-' . $distributionArray['date_distribution'];
        $assistance->setName($newDistributionName);

        $this->em->flush();
        return $assistance;
    }

    public function updateDateDistribution(Assistance $assistance, \DateTimeInterface $date)
    {
        $distributionNameWithoutDate = explode('-', $assistance->getName())[0];
        $newDistributionName = $distributionNameWithoutDate.'-'.$date->format('d-m-Y');

        $assistance
            ->setDateDistribution($date)
            ->setName($newDistributionName)
            ->setUpdatedOn(new \DateTime());

        $this->em->persist($assistance);
        $this->em->flush();
    }

    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(int $projectId, string $type)
    {
        $exportableTable = $this->em->getRepository(Assistance::class)->findBy(['project' => $projectId]);
        return $this->container->get('export_csv_service')->export($exportableTable, 'distributions', $type);
    }

    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToOfficialCsv(int $projectId, string $type)
    {
        $project = $this->em->getRepository(Project::class)->find($projectId);

        if (!$project) {
            throw new NotFoundHttpException("Project #$projectId missing");
        }

        $assistances = $this->em->getRepository(Assistance::class)->findBy(['project' => $projectId, 'archived' => 0]);
        $exportableTable = [];

        $donors = implode(', ',
            array_map(function($donor) { return $donor->getShortname(); }, $project->getDonors()->toArray())
        );

        $bnfRepo = $this->em->getRepository(Beneficiary::class);

        foreach ($assistances as $assistance)
        {
            $idps = $bnfRepo->countByResidencyStatus($assistance, "IDP");
            $residents = $bnfRepo->countByResidencyStatus($assistance, "resident");
            $maleHHH = $bnfRepo->countHouseholdHeadsByGender($assistance, Person::GENDER_MALE);
            $femaleHHH = $bnfRepo->countHouseholdHeadsByGender($assistance, Person::GENDER_FEMALE);
            $maleChildrenUnder23month = $bnfRepo->countByAgeAndByGender($assistance, 1, 0, 2, $assistance->getDateDistribution());
            $femaleChildrenUnder23month = $bnfRepo->countByAgeAndByGender($assistance, 0, 0, 2, $assistance->getDateDistribution());
            $maleChildrenUnder5years = $bnfRepo->countByAgeAndByGender($assistance, 1, 2, 6, $assistance->getDateDistribution());
            $femaleChildrenUnder5years = $bnfRepo->countByAgeAndByGender($assistance, 0, 2, 6, $assistance->getDateDistribution());
            $maleUnder17years = $bnfRepo->countByAgeAndByGender($assistance, 1, 6, 18, $assistance->getDateDistribution());
            $femaleUnder17years = $bnfRepo->countByAgeAndByGender($assistance, 0, 6, 18, $assistance->getDateDistribution());
            $maleUnder59years = $bnfRepo->countByAgeAndByGender($assistance, 1, 18, 60, $assistance->getDateDistribution());
            $femaleUnder59years = $bnfRepo->countByAgeAndByGender($assistance, 0, 18, 60, $assistance->getDateDistribution());
            $maleOver60years = $bnfRepo->countByAgeAndByGender($assistance, 1, 60, 200, $assistance->getDateDistribution());
            $femaleOver60years = $bnfRepo->countByAgeAndByGender($assistance, 0, 60, 200, $assistance->getDateDistribution());
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $assistance->getTargetType() === AssistanceTargetType::INDIVIDUAL ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $assistance->getTargetType() === AssistanceTargetType::HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $assistance->getCommodities()[0]->getModalityType()->getName();
            $beneficiaryServed =  $this->em->getRepository(Assistance::class)->getNoServed($assistance->getId(), $modalityType);

            $commodityNames = implode(', ',
                    array_map(
                        function($commodity) { return  $commodity->getModalityType()->getName(); }, 
                        $assistance->getCommodities()->toArray()
                    )
                );
            $commodityUnit = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getUnit(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );
            
            $totalAmount = implode(', ',
                array_map(
                    function($commodity) use($noFamilies) { return  $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );

            
            
            $row = [
                "Navi/Elo number" => $assistance->getProject()->getInternalId() ?? " ",
                "DISTR. NO." => $assistance->getId(),
                "Distributed by" => " ",
                "Round" => " ",
                "Donor" => $donors,
                "Starting Date" => $assistance->getDateDistribution(),
                "Ending Date" => $assistance->getCompleted() ? $assistance->getUpdatedOn() : " - ",
                "Governorate" => $assistance->getLocation()->getAdm1Name(),
                "District" => $assistance->getLocation()->getAdm2Name(),
                "Sub-District" => $assistance->getLocation()->getAdm3Name(),
                "Town, Village" => $assistance->getLocation()->getAdm4Name(),
                "Location = School/Camp" => " ",
                "Neighbourhood (Camp Name)" => " ",
                "Latitude" => " ",
                "Longitude" => " ",
                // "Location Code" => $distribution->getLocation()->getCode(),
                "Activity (Modality)" => $commodityNames,
                "UNIT" => $commodityUnit,
                "Nº Of Units" => $numberOfUnits,
                "Amount (USD/SYP)" => " ",
                "Total Amount" => $totalAmount,
                "Bebelac Type" => " ",
                "Water\nNº of 1.5 bottles " => " ",
                "Bebelac kg" => " ",
                "Nappies Pack" => " ",
                "IDPs" => $idps,
                "Residents" => $residents,
                "Nº FAMILIES" => $noFamilies,
                "FEMALE\nHead of Family gender" => $femaleHHH,
                "MALE\nHead of Family gender" => $maleHHH,
                /*
                * Male and Female children from 0 to 17 months
                */
                "Children\n0-23 months\nMale" => $maleChildrenUnder23month,
                "Children\n0-23 months\nFemale" => $femaleChildrenUnder23month,
                //"Children\n2-5" => $childrenUnder5years
                "Children\n2-5\nMale" => $maleChildrenUnder5years,
                "Children\n2-5\nFemale" => $femaleChildrenUnder5years,
                "Males\n6-17" => $maleUnder17years,
                "Females\n6-17" => $femaleUnder17years,
                "Males\n18-59" => $maleUnder59years,
                "Females\n18-59" => $femaleUnder59years,
                "Males\n60+" => $maleOver60years,
                "Females\n60+" => $femaleOver60years,
                "Total\nMales" => $maleTotal,
                "Total\nFemales" => $femaleTotal,
                "Individ. Benef.\nServed" => $beneficiaryServed,
                "Family\nSize" => $familySize
            ];
            array_push($exportableTable, $row);
        }
        return $this->container->get('export_csv_service')->export($exportableTable, 'distributions', $type);
    }

    /**
     * @param string $country
     * @return int
     */
    public function countAllBeneficiaries(string $country)
    {
        $count = (int) $this->em->getRepository(AssistanceBeneficiary::class)->countAll($country);
        return $count;
    }
    
    /**
     * @param string $country
     * @return string
     */
    public function getTotalValue(string $country)
    {
        $value = (int) $this->em->getRepository(Assistance::class)->getTotalValue($country);
        return $value;
    }

     /**
     * @param string $country
     * @return string
     */
    public function countCompleted(string $country)
    {
        $value = (int) $this->em->getRepository(Assistance::class)->countCompleted($country);
        return $value;
    }

    /**
     * @param Assistance[] $distributions
     * @return Assistance[]
     */
    public function filterDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        /** @var Assistance $key */
        foreach ($distributionArray as $key) {
            if (!$key->getArchived()) {
                $filteredArray[] = $key;
            };
        }
        return $filteredArray;
    }

    /**
     * @param $distributions
     * @return string
     */
    public function filterQrVoucherDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        foreach ($distributionArray as $distribution) {
            $commodities = $distribution->getCommodities();
            $isQrVoucher = false;
            foreach ($commodities as $commodity) {
                if ($commodity->getModalityType()->getName() === "QR Code Voucher") {
                    $isQrVoucher = true;
                }
            }
            if ($isQrVoucher && !$distribution->getArchived()) {
                $filteredArray[] = $distribution;
            };
        }
        return $filteredArray;
    }

    /**
     * @param $country
     * @return Assistance[]
     */
    public function getActiveDistributions($country)
    {
        $active = $this->em->getRepository(Assistance::class)->getActiveByCountry($country);
        return $active;
    }
    
    /**
     * Initialise GRI for a distribution
     * @param  Assistance $assistance
     * @return void
     */
    public function createGeneralReliefItems(Assistance $assistance)
    {
        $distributionBeneficiaries = $assistance->getDistributionBeneficiaries();
        foreach ($distributionBeneficiaries as $index => $assistanceBeneficiary) {
            $$index = new GeneralReliefItem();
            $$index->setAssistanceBeneficiary($assistanceBeneficiary);
            $assistanceBeneficiary->addGeneralRelief($$index);

            $this->em->persist($$index);
            $this->em->persist($assistanceBeneficiary);
        }
        $this->em->flush();
    }

    /**
     * Edit notes of general relief item
     * @param  GeneralReliefItem $generalRelief
     * @param  string            $notes
     */
    public function editGeneralReliefItemNotes(int $id, string $notes)
    {
        try {
            $generalRelief = $this->em->getRepository(GeneralReliefItem::class)->find($id);
            $generalRelief->setNotes($notes);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception("Error updating general relief item");
        }
    }

    /**
     * Set general relief items as distributed
     * @param array    $griIds
     * @param DateTime $distributedAt
     * @return array
     */
    public function setGeneralReliefItemsAsDistributed(array $griIds)
    {
        $errorArray = array();
        $successArray = array();
        foreach ($griIds as $griId) {
            $gri = $this->em->getRepository(GeneralReliefItem::class)->find($griId);

            if (!($gri instanceof GeneralReliefItem)) {
                array_push($errorArray, $griId);
            } else {
                $gri->setDistributedAt(new \DateTime());
                $this->em->persist($gri);
                array_push($successArray, $gri);
            }
        }
        $this->em->flush();

        // Checks if the distribution is completed
        $generalReliefItem = $this->em->getRepository(GeneralReliefItem::class)->find(array_pop($griIds));
        $assistance = $generalReliefItem->getAssistanceBeneficiary()->getAssistance();
        $numberIncomplete = $this->em->getRepository(GeneralReliefItem::class)->countNonDistributed($assistance);

        return array($successArray, $errorArray, $numberIncomplete);
    }

    /**
     * Set general relief item attributes
     *
     * @param GeneralReliefItem           $gri
     * @param GeneralReliefPatchInputType $input
     */
    public function patchGeneralReliefItem(GeneralReliefItem $gri, GeneralReliefPatchInputType $input)
    {
        if ($input->isDistributedSet()) {
            if ($input->getDistributed()) {
                $gri->setDistributedAt(new \DateTime($input->getDateOfDistribution()));
            } else {
                $gri->setDistributedAt(null);
            }
        }

        if ($input->isNoteSet()) {
            $gri->setNotes($input->getNote());
        }

        $this->em->persist($gri);
        $this->em->flush();
    }

    /**
     * Export all distributions in a pdf
     * @param int $projectId
     * @return mixed
     */
    public function exportToPdf(int $projectId)
    {
        $exportableTable = $this->em->getRepository(Assistance::class)->findBy(['project' => $projectId, 'archived' => false]);
        $project = $this->em->getRepository(Project::class)->find($projectId);

        try {
            $html =  $this->container->get('templating')->render(
                '@Distribution/Pdf/distributions.html.twig',
                array_merge(
                    ['project' => $project,
                    'distributions' => $exportableTable],
                    $this->container->get('pdf_service')->getInformationStyle()
                )

            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'landscape', 'bookletCodes');
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param Assistance $assistance
     */
    public function delete(Assistance $assistance)
    {
        if ($assistance->getValidated()) {
            $this->archived($assistance);

            return;
        }

        foreach ($assistance->getCommodities() as $commodity) {
            $this->em->remove($commodity);
        }
        foreach ($assistance->getSelectionCriteria() as $criterion) {
            $this->em->remove($criterion);
        }
        foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            foreach ($assistanceBeneficiary->getGeneralReliefs() as $relief) {
                $this->em->remove($relief);
            }
            $this->em->remove($assistanceBeneficiary);
        }

        $this->em->remove($assistance);
        $this->em->flush();
    }

    private function generateName(\CommonBundle\Entity\Location $location, ?\DateTimeInterface $date = null): string
    {
        $adm = '';
        if ($location->getAdm4()) {
            $adm = $location->getAdm4()->getName();
        } elseif ($location->getAdm3()) {
            $adm = $location->getAdm3()->getName();
        } elseif ($location->getAdm2()) {
            $adm = $location->getAdm2()->getName();
        } elseif ($location->getAdm1()) {
            $adm = $location->getAdm1()->getName();
        }

        if ($date) {
            return $adm.'-'.$date->format('d-m-Y');
        } else {
            return $adm.'-'.date('d-m-Y');
        }
    }

    public function mapping(AssistanceCreateInputType $inputType): array
    {
        /** @var \CommonBundle\Entity\Location $location */
        $location = $this->em->getRepository(\CommonBundle\Entity\Location::class)->find($inputType->getLocationId());

        $locationArray = [];
        if ($location->getAdm4()) {
            $locationArray = [
                'adm1' => $location->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm4()->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm4()->getAdm3()->getId(),
                'adm4' => $location->getAdm4()->getId(),
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm3()){
            $locationArray = [
                'adm1' => $location->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm3()->getId(),
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm2()){
            $locationArray = [
                'adm1' => $location->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm2()->getId(),
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm1()){
            $locationArray = [
                'adm1' => $location->getAdm1()->getId(),
                'adm2' => null,
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        }



        $distributionArray = [
            'countryIso3' => $inputType->getIso3(),
            'assistance_type' => $inputType->getType(),
            'target_type' => $inputType->getTarget(),
            'date_distribution' => $inputType->getDateDistribution(),
            'project' => ['id' => $inputType->getProjectId()],
            'location' => $locationArray,
            'sector' => $inputType->getSector(),
            'subsector' => $inputType->getSubsector(),
            'threshold' => $inputType->getThreshold(),
            'institutions' => $inputType->getInstitutions(),
            'communities' => $inputType->getCommunities(),
            'households_targeted' => $inputType->getHouseholdsTargeted(),
            'individuals_targeted' => $inputType->getIndividualsTargeted(),
            'description' => $inputType->getDescription(),
        ];

        foreach ($inputType->getCommodities() as $commodity) {
            $modalityType = $this->em->getRepository(ModalityType::class)->findOneBy(['name' => $commodity->getModalityType()]);
            if (!$modalityType) {
                throw new EntityNotFoundException(sprintf('ModalityType %s does not exists', $commodity->getModalityType()));
            }
            $distributionArray['commodities'][] = [
                'value' => $commodity->getValue(),
                'unit' => $commodity->getUnit(),
                'description' => $commodity->getDescription(),
                'modality_type' => ['id' => $modalityType->getId()],
            ];
        }

        foreach ($inputType->getSelectionCriteria() as $criterion) {
            $distributionArray['selection_criteria'][$criterion->getGroup()][] = $this->fieldDbTransformer->toDbArray($criterion);
        }

        return $distributionArray;
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportGeneralReliefDistributionToCsv(Assistance $assistance, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        $generalreliefs = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $db) {
            $generalrelief = $this->em->getRepository(GeneralReliefItem::class)->findOneByAssistanceBeneficiary($db);

            if ($generalrelief) {
                array_push($generalreliefs, $generalrelief);
            }
        }

        foreach ($generalreliefs as $generalrelief) {
            $beneficiary = $generalrelief->getAssistanceBeneficiary()->getBeneficiary();
            $commodityNames = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getModalityType()->getName(); },
                    $assistance->getCommodities()->toArray()
                )
            );


            $commodityValues = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue() . ' ' . $commodity->getUnit(); },
                    $assistance->getCommodities()->toArray()
                )
            );

            $commonFields = $beneficiary->getCommonExportFields();

            array_push($exportableTable,
                array_merge($commonFields, array(
                    "Commodity" => $commodityNames,
                    "Value" => $commodityValues,
                    "Distributed At" => $generalrelief->getDistributedAt(),
                    "Notes Distribution" => $generalrelief->getNotes(),
                    "Removed" => $generalrelief->getAssistanceBeneficiary()->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $generalrelief->getAssistanceBeneficiary()->getJustification(),
                ))
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'generalrelief', $type);
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportVouchersDistributionToCsv(Assistance $assistance, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findByAssistance($assistance);

        $beneficiaries = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
            $beneficiary = $assistanceBeneficiary->getBeneficiary();
            $booklets = $assistanceBeneficiary->getBooklets();
            $transactionBooklet = null;
            if (count($booklets) > 0) {
                foreach ($booklets as $booklet) {
                    if ($booklet->getStatus() !== 3) {
                        $transactionBooklet = $booklet;
                    }
                }
                if ($transactionBooklet === null) {
                    $transactionBooklet = $booklets[0];
                }
            }

            $commonFields = $beneficiary->getCommonExportFields();

            $products = [];
            if ($transactionBooklet) {
                /** @var Voucher $voucher */
                foreach ($transactionBooklet->getVouchers() as $voucher) {
                    if ($voucher->getVoucherPurchase()) {
                        foreach ($voucher->getVoucherPurchase()->getRecords() as $record) {
                            array_push($products, $record->getProduct()->getName());
                        }
                    }
                }
            }
            $products = implode(', ', array_unique($products));

            array_push(
                $exportableTable,
                array_merge($commonFields, array(
                    "Booklet" => $transactionBooklet ? $transactionBooklet->getCode() : null,
                    "Status" => $transactionBooklet ? $transactionBooklet->getStatus() : null,
                    "Value" => $transactionBooklet ? $transactionBooklet->getTotalValue() . ' ' . $transactionBooklet->getCurrency() : null,
                    "Used At" => $transactionBooklet ? $transactionBooklet->getUsedAt() : null,
                    "Purchased items" => $products,
                    "Removed" => $assistanceBeneficiary->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $assistanceBeneficiary->getJustification(),
                ))
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'qrVouchers', $type);
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportToCsvBeneficiariesInDistribution(Assistance $assistance, string $type)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getNotRemovedofDistribution($assistance);
        return $this->container->get('export_csv_service')->export($beneficiaries, 'beneficiaryInDistribution', $type);
    }

    /**
     * Check if possible to revert validate state of assistance
     *
     * @param Assistance $assistance
     *
     * @return bool
     */
    public function isDistributionStarted(Assistance $assistance): bool
    {
        /** @var AssistanceStatistics $statistics */
        $statistics = $this->em->getRepository(AssistanceStatistics::class)->findByAssistance($assistance);

        return empty($statistics->getAmountDistributed());
    }
}
