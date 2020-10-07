<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Person;
use CommonBundle\Utils\LocationService;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;

/**
 * Class DistributionService
 * @package DistributionBundle\Utils
 */
class DistributionService
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

    /** @var CriteriaDistributionService $criteriaDistributionService */
    private $criteriaDistributionService;

    /** @var AbstractRetriever $retriever */
    private $retriever;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * DistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param LocationService $locationService
     * @param CommodityService $commodityService
     * @param ConfigurationLoader $configurationLoader
     * @param CriteriaDistributionService $criteriaDistributionService
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
        CriteriaDistributionService $criteriaDistributionService,
        string $classRetrieverString,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->configurationLoader = $configurationLoader;
        $this->criteriaDistributionService = $criteriaDistributionService;
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
                    $generalRelief->setDistributionBeneficiary($beneficiary);
                    $this->em->persist($generalRelief);
                }
            }
        }
        $this->em->flush();

        return $assistance;
    }

    /**
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @param int $threshold
     * @return array
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function create($countryISO3, array $distributionArray, int $threshold)
    {
        $location = $distributionArray['location'];
        unset($distributionArray['location']);

        $selectionCriteriaGroup = $distributionArray['selection_criteria'];
        unset($distributionArray['selection_criteria']);

        $distributionArray['assistance_type'] = AssistanceTypeEnum::DISTRIBUTION;
        $distributionArray['target_type'] = $distributionArray['type'];
        unset($distributionArray['type']);
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

        // TODO : make the front send 0 or 1 instead of Individual (Beneficiary comes from the import)
        if ($distributionArray['target_type'] === "Beneficiary" || $distributionArray['target_type'] === "Individual" || $distributionArray['target_type'] === "1") {
            $distribution->setTargetType(Assistance::TYPE_BENEFICIARY);
        } else {
            $distribution->setTargetType(Assistance::TYPE_HOUSEHOLD);
        }

        $location = $this->locationService->getLocation($countryISO3, $location);
        $distribution->setLocation($location);

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->findOneBy([]);
        if ($projectTmp instanceof Project) {
            $distribution->setProject($projectTmp);
        }


        foreach ($distribution->getCommodities() as $item) {
            $distribution->removeCommodity($item);
        }
        foreach ($distributionArray['commodities'] as $item) {
            $this->commodityService->create($distribution, $item, false);
        }

        $criteria = [];
        foreach ($selectionCriteriaGroup as $i => $criteriaData) {
            foreach ($criteriaData as $j => $criterionArray) {
                /** @var SelectionCriteria $criterion */
                $criterion = $this->serializer->deserialize(json_encode($criterionArray), SelectionCriteria::class, 'json');
                $criterion->setGroupNumber($i);
                $this->criteriaDistributionService->save($distribution, $criterion, false);
                $criteria[$i][$j] = $criterionArray;
            }
        }

        $this->em->persist($distribution);
        $this->em->flush();

        $this->em->persist($distribution);

        $distributionArray['selection_criteria'] = $criteria;

        $listReceivers = $this->guessBeneficiaries($distributionArray, $countryISO3, $distributionArray['target_type'], $projectTmp, $threshold);
        $this->saveReceivers($distribution, $listReceivers);

        $this->em->flush();

        return ["distribution" => $distribution, "data" => $listReceivers];
    }

    /**
     * @param array $criteria
     * @param $countryISO3
     * @param $type
     * @param Project $project
     * @param int $threshold
     * @return mixed
     */
    public function guessBeneficiaries(array $criteria, $countryISO3, $type, Project $project, int $threshold)
    {
        $criteria['criteria'] = $criteria['selection_criteria'];
        $criteria['countryIso3'] = $countryISO3;
        $criteria['distribution_type'] = $type;

        return $this->container->get('distribution.criteria_distribution_service')->load($criteria, $project, $threshold, false);
    }

    /**
     * @param Assistance $assistance
     * @param array $listReceivers
     * @throws \Exception
     */
    public function saveReceivers(Assistance $assistance, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver) {
        $distributionBeneficiary = new DistributionBeneficiary();
        $distributionBeneficiary->setAssistance($assistance)
            ->setBeneficiary($this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $receiver))
            ->setRemoved(0);
           
            $this->em->persist($distributionBeneficiary);
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
                $assistance->setCompleted(1)
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
        $distributions = $this->em->getRepository(Assistance::class)->findBy(['project' => $projectId]);
        $project = $this->em->getRepository(Project::class)->find($projectId);
        $exportableTable = [];

        $donors = implode(', ',
            array_map(function($donor) { return $donor->getShortname(); }, $project->getDonors()->toArray())
        );
        
        foreach ($distributions as $distribution) {

            $idps = $this->em->getRepository(Assistance::class)->getNoBenificiaryByResidencyStatus($distribution->getId(), "IDP", $distribution->getAssistanceType());
            $residents = $this->em->getRepository(Assistance::class)->getNoBenificiaryByResidencyStatus($distribution->getId(), "resident", $distribution->getAssistanceType());
            $maleHHH = $this->em->getRepository(Assistance::class)->getNoHeadHouseholdsByGender($distribution->getId(), Person::GENDER_MALE);
            $femaleHHH = $this->em->getRepository(Assistance::class)->getNoHeadHouseholdsByGender($distribution->getId(), Person::GENDER_FEMALE);
            $maleChildrenUnder23month = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 0, 2, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $femaleChildrenUnder23month = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 0, 2, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $maleChildrenUnder5years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 2, 6, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $femaleChildrenUnder5years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 2, 6, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $maleUnder17years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 6, 18, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $femaleUnder17years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 6, 18, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $maleUnder59years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 18, 60, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $femaleUnder59years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 18, 60, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $maleOver60years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 60, 200, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $femaleOver60years = $this->em->getRepository(Assistance::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 60, 200, $distribution->getDateDistribution(), $distribution->getAssistanceType());
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $distribution->getTargetType() === Assistance::TYPE_BENEFICIARY ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $distribution->getTargetType() === Assistance::TYPE_HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $distribution->getCommodities()[0]->getModalityType()->getName();
            $beneficiaryServed =  $this->em->getRepository(Assistance::class)->getNoServed($distribution->getId(), $modalityType);

            $commodityNames = implode(', ',
                    array_map(
                        function($commodity) { return  $commodity->getModalityType()->getName(); }, 
                        $distribution->getCommodities()->toArray()
                    )
                );
            $commodityUnit = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getUnit(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );
            
            $totalAmount = implode(', ',
                array_map(
                    function($commodity) use($noFamilies) { return  $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );

            
            
            $row = [
                "Navi/Elo number" => $distribution->getProject()->getInternalId() ?? " ",
                "DISTR. NO." => $distribution->getId(),
                "Distributed by" => " ",
                "Round" => " ",
                "Donor" => $donors,
                "Starting Date" => $distribution->getDateDistribution(),
                "Ending Date" => $distribution->getCompleted() ? $distribution->getUpdatedOn() : " - ",
                "Governorate" => $distribution->getLocation()->getAdm1Name(),
                "District" => $distribution->getLocation()->getAdm2Name(),
                "Sub-District" => $distribution->getLocation()->getAdm3Name(),
                "Town, Village" => $distribution->getLocation()->getAdm4Name(),
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
        $count = (int) $this->em->getRepository(DistributionBeneficiary::class)->countAll($country);
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
        foreach ($distributionBeneficiaries as $index => $distributionBeneficiary) {
            $$index = new GeneralReliefItem();
            $$index->setDistributionBeneficiary($distributionBeneficiary);
            $distributionBeneficiary->addGeneralRelief($$index);

            $this->em->persist($$index);
            $this->em->merge($distributionBeneficiary);
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
                $this->em->merge($gri);
                array_push($successArray, $gri);
            }
        }
        $this->em->flush();

        // Checks if the distribution is completed
        $generalReliefItem = $this->em->getRepository(GeneralReliefItem::class)->find(array_pop($griIds));
        $assistance = $generalReliefItem->getDistributionBeneficiary()->getAssistance();
        $numberIncomplete = $this->em->getRepository(GeneralReliefItem::class)->countNonDistributed($assistance);
        
        if ($numberIncomplete === '0') {
            $this->complete($assistance);
        }
        
        return array($successArray, $errorArray, $numberIncomplete);
    }
    
    /**
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportGeneralReliefDistributionToCsv(Assistance $assistance, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findByAssistance($assistance);

        $generalreliefs = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $db) {
            $generalrelief = $this->em->getRepository(GeneralReliefItem::class)->findOneByDistributionBeneficiary($db);

            if ($generalrelief) {
                array_push($generalreliefs, $generalrelief);
            }
        }

        foreach ($generalreliefs as $generalrelief) {
            $beneficiary = $generalrelief->getDistributionBeneficiary()->getBeneficiary();
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
                    "Removed" => $generalrelief->getDistributionBeneficiary()->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $generalrelief->getDistributionBeneficiary()->getJustification(),
                ))
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'generalrelief', $type);
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
     * Export a distribution in pdf
     * @param int $distributionId
     * @return mixed
     */
    public function exportOneToPdf(int $distributionId)
    {
        $exportableDistribution = $this->em->getRepository(Assistance::class)->findOneBy(['id' => $distributionId, 'archived' => false]);

        $booklets = [];

        if ($exportableDistribution->getCommodities()[0]->getModalityType()->getName() === 'QR Code Voucher') {
            foreach ($exportableDistribution->getDistributionBeneficiaries() as $distributionBeneficiary) {
                    $activatedBooklets = $this->em->getRepository(Booklet::class)->getActiveBookletsByDistributionBeneficiary($distributionBeneficiary->getId());
                    if (count($activatedBooklets) > 0) {
                        $products = $this->em->getRepository(Product::class)->getNameByBooklet($activatedBooklets[0]->getId());
                        $products = array_map(
                            function($product) { return $product['name']; },
                            $products
                        );
                        $booklet = [
                            "code" => $activatedBooklets[0]->getCode(),
                            "status" => $activatedBooklets[0]->getStatus(),
                            "vouchers" => $activatedBooklets[0]->getVouchers(),
                            "products" => implode(', ', $products),
                            "value" => $activatedBooklets[0]->getTotalValue(),
                            "currency" => $activatedBooklets[0]->getCurrency(),
                            "usedAt" => $activatedBooklets[0]->getUsedAt()
                        ];
                        $booklets[$distributionBeneficiary->getId()] = $booklet;
                    }
            }
        }

        try {
            $html =  $this->container->get('templating')->render(
                '@Distribution/Pdf/distribution.html.twig',
                array_merge(
                    [
                        'distribution' => $exportableDistribution,
                        'booklets' => $booklets,
                        'commodities' => implode(', ', array_map(
                            function($commodity) { return $commodity->getValue() . ' ' . $commodity->getUnit() . '/pers'; }, 
                            $exportableDistribution->getCommodities()->toArray()
                        ))
                    ],
                    $this->container->get('pdf_service')->getInformationStyle()
                )
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'bookletCodes');
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


}
