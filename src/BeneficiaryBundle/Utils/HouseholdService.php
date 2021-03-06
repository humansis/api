<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\CampAddress;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdProxyInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class HouseholdService
 * @package BeneficiaryBundle\Utils
 */
class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;


    /**
     * HouseholdService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param BeneficiaryService $beneficiaryService
     * @param RequestValidator $requestValidator
     * @param LocationService $locationService
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator,
        LocationService $locationService,
        ValidatorInterface $validator,
        ContainerInterface $container
    )
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
        $this->validator = $validator;
        $this->container = $container;
    }

    /**
     * @param string $iso3
     * @param array $filters
     * @return mixed
     */
    public function getAll(string $iso3, array $filters)
    {
        $pageIndex = $filters['pageIndex'];
        $pageSize = $filters['pageSize'];
        $filter = $filters['filter'];
        $sort = $filters['sort'];

        $limitMinimum = $pageIndex * $pageSize;

        $households = $this->em->getRepository(Household::class)->getAllBy($iso3, $limitMinimum, $pageSize, $sort, $filter);
        $length = $households[0];
        $households = $households[1];

        return [$length, $households];
    }

    public function create(HouseholdCreateInputType $inputType): Household
    {
        $data = $this->fallbackMap($inputType);

        return $this->createOrEdit($data, $inputType->getProjectIds());
    }

    public function update(Household $household, HouseholdUpdateInputType $inputType): Household
    {
        $data = $this->fallbackMap($inputType);

        return $this->createOrEdit($data, $inputType->getProjectIds(), $household);
    }

    /**
     * @param array $householdArray
     * @param $projectsArray
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     * @deprecated
     */
    public function createOrEdit(array $householdArray, array $projectsArray, $household = null, bool $flush = true)
    {
        if (!empty($projectsArray) && (gettype($projectsArray[0]) === 'string' || gettype($projectsArray[0]) === 'integer')) {
            $projectsArray = $this->em->getRepository(Project::class)->findBy(["id" => $projectsArray]);
        }
        $actualAction = 'update';
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        if (!$household) {
            $actualAction = 'create';
            $household = new Household();
        }

        if ($household->getHouseholdLocations()) {
            foreach ($household->getHouseholdLocations() as $initialHouseholdLocation) {
                $this->em->remove($initialHouseholdLocation);
            }
        }
        $this->em->flush();

        foreach ($householdArray['household_locations'] as $householdLocation) {
            $newHouseholdLocation = new HouseholdLocation();
            $newHouseholdLocation
                ->setLocationGroup($householdLocation['location_group'])
                ->setType($householdLocation['type']);

            if ($householdLocation['type'] === HouseholdLocation::LOCATION_TYPE_CAMP) {
                // Try to find the camp with the name in the request
                $camp = $this->em->getRepository(Camp::class)->findOneBy(['name' => $householdLocation['camp_address']['camp']['name']]);
                // Or create a camp with the name in the request
                if (!$camp instanceof Camp) {
                    $location = $this->locationService->getLocation($householdArray['__country'], $householdLocation['camp_address']['camp']['location']);
                    if (null === $location) {
                        throw new \Exception("Location was not found.");
                    }
                    $camp = new Camp();
                    $camp->setName($householdLocation['camp_address']['camp']['name']);
                    $camp->setLocation($location);
                }
                $campAddress = new CampAddress();
                $campAddress->setTentNumber($householdLocation['camp_address']['tent_number'])
                    ->setCamp($camp);
                $newHouseholdLocation->setCampAddress($campAddress);
            } else {
                $location = $this->locationService->getLocation($householdArray['__country'], $householdLocation['address']["location"]);
                if (null === $location) {
                    throw new \Exception("Location was not found.");
                }
                $newHouseholdLocation->setAddress(Address::create(
                    $householdLocation['address']['street'] ?? null,
                    $householdLocation['address']['number'] ?? null,
                    $householdLocation['address']['postcode'] ?? null,
                    $location
                    ));
            }
            $household->addHouseholdLocation($newHouseholdLocation);
            $this->em->persist($newHouseholdLocation);
        }


        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setIncomeLevel($householdArray["income_level"] ?? null)
            ->setCopingStrategiesIndex($householdArray["coping_strategies_index"])
            ->setFoodConsumptionScore($householdArray["food_consumption_score"])
            ->setAssets($householdArray["assets"] ?? [])
            ->setShelterStatus($householdArray["shelter_status"] ?? null)
            ->setDebtLevel($householdArray["debt_level"] ?? null)
            ->setSupportReceivedTypes($householdArray["support_received_types"] ?? [])
            ->setSupportOrganizationName($householdArray["support_organization_name"] ?? null)
            ->setIncomeSpentOnFood($householdArray["income_spent_on_food"] ?? null)
            ->setHouseholdIncome($householdArray["household_income"] ?? null)
            ->setEnumeratorName($householdArray["enumerator_name"] ?? null);

        $dateReceived = null;
        if (isset($householdArray["support_date_received"]) && $householdArray["support_date_received"]) {
            if (is_string($householdArray['support_date_received'])) {
                $dateReceived = \DateTime::createFromFormat('d-m-Y', $householdArray['support_date_received']);
            } else {
                $dateReceived = $householdArray['support_date_received'];
            }

            if (!$dateReceived instanceof \DateTimeInterface) {
                throw new \Exception("Value of support_date_received is invalid");
            }
        }
        $household->setSupportDateReceived($dateReceived);

        // Remove projects if the household is not part of them anymore
        if ($actualAction === "update") {
            $oldProjects = $household->getProjects()->toArray();
            $toRemove = array_udiff(
                $oldProjects,
                $projectsArray,
                function ($oldProject, $newProject) {
                    if ($oldProject->getId() === $newProject->getId()) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            foreach ($toRemove as $projectToRemove) {
                $household->removeProject($projectToRemove);
            }
        }

        // Add projects
        foreach ($projectsArray as $project) {
            if (!$project instanceof Project) {
                throw new \Exception("The project could not be found.");
            }
            if ($actualAction !== 'update' || !$household->getProjects()->contains($project)) {
                $household->addProject($project);
            }
        }

        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"])) {
            $hasHead = false;
            $beneficiariesPersisted = [];
            if ($actualAction === "update") {
                $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findBy(["household" => $household]);
            }
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave) {
                try {
                    if ($beneficiaryToSave['gender'] === 'Male') {
                        $beneficiaryToSave['gender'] = Person::GENDER_MALE;
                    } elseif ($beneficiaryToSave['gender'] === 'Female') {
                        $beneficiaryToSave['gender'] = Person::GENDER_FEMALE;
                    }

                    $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
                    if (!array_key_exists("id", $beneficiaryToSave)) {
                        $household->addBeneficiary($beneficiary);
                    }
                    $beneficiariesPersisted[] = $beneficiary;
                } catch (\Exception $exception) {
                    throw $exception;
                }
                if ($beneficiary->isHead()) {
                    if ($hasHead) {
                        throw new \Exception("You have defined more than 1 head of household.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
            }

            // Remove beneficiaries that are not in the household anymore
            if ($actualAction === 'update') {
                $toRemove = array_udiff(
                    $oldBeneficiaries,
                    $beneficiariesPersisted,
                    function ($oldB, $newB) {
                        if ($oldB->getId() === $newB->getId()) {
                            return 0;
                        } else {
                            return -1;
                        }
                    }
                );
                foreach ($toRemove as $beneficiaryToRemove) {
                    $household->removeBeneficiary($beneficiaryToRemove);
                    $this->beneficiaryService->remove($beneficiaryToRemove);
                }
            }
        }

        if (!empty($householdArray["country_specific_answers"])) {
            foreach ($householdArray["country_specific_answers"] as $country_specific_answer) {
                $this->addOrUpdateCountrySpecific($household, $country_specific_answer, false);
            }
        }

        $proxy = $household->getProxy();

        if (array_key_exists('proxy', $householdArray) && (null !== $householdArray['proxy']['localGivenName'] || null !== $householdArray['proxy']['localFamilyName']) ) {
            if (null === $proxy) {
                $proxy = new Person();
                $this->em->persist($proxy);
                $household->setProxy($proxy);
            }

            $proxyArray = $householdArray['proxy'];

            $proxy->setEnGivenName($proxyArray['enGivenName']);
            $proxy->setEnFamilyName($proxyArray['enFamilyName']);
            $proxy->setEnParentsName($proxyArray['enParentsName']);
            $proxy->setLocalGivenName($proxyArray['localGivenName']);
            $proxy->setLocalFamilyName($proxyArray['localFamilyName']);
            $proxy->setLocalParentsName($proxyArray['localParentsName']);

            /** @var PhoneInputType $phoneInputType */
            $phoneInputType = $proxyArray['phone'];

            $proxy->getPhones()->clear();

            $phone = new Phone();
            $phone->setType($phoneInputType->getType());
            $phone->setPrefix($phoneInputType->getPrefix());
            $phone->setNumber($phoneInputType->getNumber());
            $phone->setProxy($phoneInputType->getProxy());
            $phone->setPerson($proxy);

            $this->em->persist($phone);

            /** @var NationalIdCardInputType $nationalIdInputType */
            $nationalIdInputType = $proxyArray['nationalIdCard'];

            $proxy->getNationalIds()->clear();

            $nationalId = new NationalId();
            $nationalId->setIdType($nationalIdInputType->getType());
            $nationalId->setIdNumber($nationalIdInputType->getNumber());
            $nationalId->setPerson($proxy);

            $this->em->persist($nationalId);

        } else {
            if (null !== $proxy) {
                $this->em->remove($proxy);
            }

            $household->setProxy(null);
        }

        if ($flush) {
            $this->em->flush();
            $household = $this->em->getRepository(Household::class)->find($household->getId());
            $country_specific_answers = $this->em->getRepository(CountrySpecificAnswer::class)->findByHousehold($household);
            foreach ($country_specific_answers as $country_specific_answer) {
                $household->addCountrySpecificAnswer($country_specific_answer);
            }
        }


        return $household;
    }

    /**
     * @param array $householdArray
     * @return array
     */
    public function removeBeneficiaries(array $householdArray)
    {
        $household = $this->em->getRepository(Household::class)->find($householdArray['id']);
        $beneficiaryIds = array_map(function ($beneficiary) {
            return $beneficiary['id'];
        }, $householdArray['beneficiaries']);

        // Remove beneficiaries that are not in the array
        foreach ($household->getBeneficiaries() as $beneficiary) {
            if (!in_array($beneficiary->getId(), $beneficiaryIds)) {
                $this->em->remove($beneficiary);
            }
        }

        return $householdArray;
    }

    /**
     * @param Household $household
     * @param Project $project
     */
    public function addToProject(Household &$household, Project $project)
    {
        if (!$household->getProjects()->contains($project)) {
            $household->addProject($project);
            $this->em->persist($household);
        }
    }

    /**
     * @param Household $household
     * @param array $countrySpecificAnswerArray
     * @return array|CountrySpecificAnswer
     * @throws \Exception
     */
    public function addOrUpdateCountrySpecific(Household $household, array $countrySpecificAnswerArray, bool $flush)
    {
        $this->requestValidator->validate(
            "country_specific_answer",
            HouseholdConstraints::class,
            $countrySpecificAnswerArray,
            'any'
        );
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->find($countrySpecificAnswerArray["country_specific"]["id"]);

        if (!$countrySpecific instanceof CountrySpecific) {
            throw new \Exception("This country specific is unknown");
        }

        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $household
            ]);

        if ($countrySpecificAnswerArray["answer"]) {
            if (!$countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $countrySpecificAnswer = new CountrySpecificAnswer();
                $countrySpecificAnswer->setCountrySpecific($countrySpecific)
                    ->setHousehold($household);
            }

            $countrySpecificAnswer->setAnswer($countrySpecificAnswerArray["answer"]);

            $this->em->persist($countrySpecificAnswer);
        } else {
            if ($countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $this->em->remove($countrySpecificAnswer);
            }
        }

        if ($flush) {
            $this->em->flush();
        }

        return $countrySpecificAnswer;
    }

    public function remove(Household $household)
    {
        $household->setArchived(true);
        $this->em->persist($household);
        $this->em->flush();

        return $household;
    }

    public function removeMany(array $householdIds)
    {
        foreach ($householdIds as $householdId) {
            $household = $this->em->getRepository(Household::class)->find($householdId);
            $household->setArchived(true);
            $this->em->persist($household);
        }
        $this->em->flush();
        return "Households have been archived";
    }


    /**
     * @return mixed
     */
    public function exportToCsv()
    {
        $exportableTable = $this->em->getRepository(Household::class)->findAll();
        return $this->container->get('export_csv_service')->export($exportableTable);
    }

    /**
     * @param array $householdsArray
     * @return array
     */
    public function getAllImported(array $householdsArray)
    {
        $householdsId = $householdsArray['households'];

        $households = array();

        foreach ($householdsId as $householdId) {
            $household = $this->em->getRepository(Household::class)->find($householdId);

            if ($household instanceof Household) {
                array_push($households, $household);
            }
        }

        return $households;
    }

    private function fallbackMap(HouseholdUpdateInputType $inputType)
    {
        $countrySpecificAnswers = [];
        foreach ($inputType->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            $countrySpecificAnswers[] = [
                'country_specific' => ['id' => $countrySpecificAnswer->getCountrySpecificId()],
                'answer' => $countrySpecificAnswer->getAnswer(),
            ];
        }

        $data = [
            '__country' => $inputType->getIso3(),
            'notes' => $inputType->getNotes(),
            'livelihood' => $inputType->getLivelihood(),
            'longitude' => $inputType->getLongitude(),
            'latitude' => $inputType->getLatitude(),
            'income_level' => $inputType->getIncomeLevel(),
            'coping_strategies_index' => $inputType->getCopingStrategiesIndex(),
            'food_consumption_score' => $inputType->getFoodConsumptionScore(),
            'assets' => $inputType->getAssets(),
            'shelter_status' => $inputType->getShelterStatus(),
            'debt_level' => $inputType->getDebtLevel(),
            'support_received_types' => $inputType->getSupportReceivedTypes(),
            'support_date_received' => $inputType->getSupportDateReceived() ? $inputType->getSupportDateReceived()->format('d-m-Y') : null,
            'support_organization_name' => $inputType->getSupportOrganizationName(),
            'income_spent_on_food' => $inputType->getIncomeSpentOnFood(),
            'household_income' => $inputType->getHouseIncome(),
            'enumerator_name' => $inputType->getEnumeratorName(),
            'country_specific_answers' => $countrySpecificAnswers,
        ];

        if ($inputType->getResidenceAddress()) {
            $location = $this->em->getRepository(Location::class)->find($inputType->getResidenceAddress()->getLocationId());
            if (!$location) {
                throw new EntityNotFoundException(sprintf('Location #%s does not exists', $inputType->getResidenceAddress()->getLocationId()));
            }

            $data['household_locations'][] = [
                'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                'type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE,
                'address' => [
                    'location' => [
                        'adm1' => $location->getAdm1Id(),
                        'adm2' => $location->getAdm2Id(),
                        'adm3' => $location->getAdm3Id(),
                        'adm4' => $location->getAdm4Id(),
                    ],
                    'street' => $inputType->getResidenceAddress()->getStreet(),
                    'number' => $inputType->getResidenceAddress()->getNumber(),
                    'postcode' => $inputType->getResidenceAddress()->getPostcode(),
                ],
            ];
        }

        if ($inputType->getTemporarySettlementAddress()) {
            $location = $this->em->getRepository(Location::class)->find($inputType->getTemporarySettlementAddress()->getLocationId());
            if (!$location) {
                throw new EntityNotFoundException(sprintf('Location #%s does not exists', $inputType->getTemporarySettlementAddress()->getLocationId()));
            }

            $data['household_locations'][] = [
                'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                'type' => HouseholdLocation::LOCATION_TYPE_SETTLEMENT,
                'address' => [
                    'location' => [
                        'adm1' => $location->getAdm1Id(),
                        'adm2' => $location->getAdm2Id(),
                        'adm3' => $location->getAdm3Id(),
                        'adm4' => $location->getAdm4Id(),
                    ],
                    'street' => $inputType->getTemporarySettlementAddress()->getStreet(),
                    'number' => $inputType->getTemporarySettlementAddress()->getNumber(),
                    'postcode' => $inputType->getTemporarySettlementAddress()->getPostcode(),
                ],
            ];
        }

        if ($inputType->getCampAddress()) {
            $campName = $location = null;
            if ($inputType->getCampAddress()->getCampId()) {
                /** @var Camp $camp */
                $camp = $this->em->getRepository(Camp::class)->find($inputType->getCampAddress()->getCampId());
                $campName = $camp->getName();
                $location = $camp->getLocation();
            } else {
                $campName = $inputType->getCampAddress()->getCamp()->getName();
                $location = $this->em->getRepository(Location::class)->find($inputType->getCampAddress()->getCamp()->getLocationId());
                if (!$location) {
                    throw new EntityNotFoundException(sprintf('Location #%s does not exists', $inputType->getCampAddress()->getCamp()->getLocationId()));
                }
            }


            $data['household_locations'][] = [
                'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                'type' => HouseholdLocation::LOCATION_TYPE_CAMP,
                'camp_address' => [
                    'tent_number' => $inputType->getCampAddress()->getTentNumber(),
                    'camp' => [
                        'location' => [
                            'adm1' => $location->getAdm1Id(),
                            'adm2' => $location->getAdm2Id(),
                            'adm3' => $location->getAdm3Id(),
                            'adm4' => $location->getAdm4Id(),
                        ],
                        'name' => $campName,
                    ],
                ],
            ];
        }

        foreach ($inputType->getBeneficiaries() as $bnf) {
            $vulnerabilityCriteria = [];
            foreach ($bnf->getVulnerabilityCriteria() as $name) {
                $criterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneBy(['fieldString' => $name]);
                $vulnerabilityCriteria[] = ['id' => $criterion->getId()];
            }

            $phones = [];
            foreach ($bnf->getPhones() as $phone) {
                $phones[] = [
                    'type' => $phone->getType(),
                    'prefix' => $phone->getPrefix(),
                    'number' => $phone->getNumber(),
                    'proxy' => $phone->getProxy(),
                ];
            }

            $nationalIds = [];
            foreach ($bnf->getNationalIdCards() as $nationalIdCard) {
                $nationalIds[] = [
                    'id_type' => $nationalIdCard->getType(),
                    'id_number' => $nationalIdCard->getNumber(),
                ];
            }

            $data['beneficiaries'][] = [
                'gender' => $bnf->getGender(),
                'date_of_birth' => $bnf->getDateOfBirth()->format('d-m-Y'),
                'en_family_name' => $bnf->getEnFamilyName(),
                'en_given_name' => $bnf->getEnGivenName(),
                'en_parents_name' => $bnf->getEnParentsName(),
                'local_family_name' => $bnf->getLocalFamilyName(),
                'local_given_name' => $bnf->getLocalGivenName(),
                'local_parents_name' => $bnf->getLocalParentsName(),
                'status' => $bnf->isHead() ? 1 : 0,
                'residency_status' => $bnf->getResidencyStatus(),
                'vulnerability_criteria' => $vulnerabilityCriteria,
                'phones' => $phones,
                'national_ids' => $nationalIds,
                'referral_type' => $bnf->getReferralType(),
                'referral_comment' => $bnf->getReferralComment(),
                'profile' => ['photo' => ''],
            ];
        }

        $data['proxy'] = [
            'localFamilyName' => $inputType->getProxyLocalFamilyName(),
            'localGivenName' => $inputType->getProxyLocalGivenName(),
            'localParentsName' => $inputType->getProxyLocalParentsName(),
            'enFamilyName' => $inputType->getProxyEnFamilyName(),
            'enGivenName' => $inputType->getProxyEnGivenName(),
            'enParentsName' => $inputType->getProxyEnParentsName(),
            'nationalIdCard' => $inputType->getProxyNationalIdCard(),
            'phone' => $inputType->getProxyPhone(),
        ];

        return $data;
    }
}
