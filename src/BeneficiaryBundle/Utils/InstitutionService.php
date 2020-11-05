<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Form\InstitutionConstraints;
use CommonBundle\Utils\LocationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use CommonBundle\InputType as GlobalInputType;
use BeneficiaryBundle\InputType;

/**
 * Class InstitutionService
 * @package BeneficiaryBundle\Utils
 */
class InstitutionService
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
     * InstitutionService constructor.
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
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
        $this->validator = $validator;
        $this->container= $container;
    }

    /**
     * @param GlobalInputType\Country $country
     * @param GlobalInputType\DataTableType $dataTableType
     * @return mixed
     */
    public function getAll(GlobalInputType\Country $country, GlobalInputType\DataTableType $dataTableType)
    {
        $limitMinimum = $dataTableType->pageIndex * $dataTableType->pageSize;

        $institutions = $this->em->getRepository(Institution::class)->getAllBy($country, $limitMinimum, $dataTableType->pageSize, $dataTableType->getSort());
        $length = $institutions[0];
        $institutions = $institutions[1];

        return [$length, $institutions];
    }

    /**
     * @param GlobalInputType\Country      $country
     * @param InputType\NewInstitutionType $institutionType
     *
     * @return Institution
     * @throws \InvalidArgumentException
     */
    public function create(GlobalInputType\Country $country, InputType\NewInstitutionType $institutionType): Institution
    {
        $institution = new Institution();
        $institution->setName($institutionType->getName());
        $institution->setType($institutionType->getType());
        $institution->setLongitude($institutionType->getLongitude());
        $institution->setLatitude($institutionType->getLatitude());
        $institution->setContactName($institutionType->getContactName());
        $institution->setContactFamilyName($institutionType->getContactFamilyName());
        if ($institutionType->getPhoneNumber()) {
            $institution->setPhone(new Phone());
            $institution->getPhone()->setType('Institution contact');
            $institution->getPhone()->setPrefix($institutionType->getPhonePrefix());
            $institution->getPhone()->setNumber($institutionType->getPhoneNumber());
        }


        if ($institutionType->getNationalId() !== null && !$institutionType->getNationalId()->isEmpty()) {
            $institution->setNationalId(new NationalId());
            $institution->getNationalId()->setIdNumber($institutionType->getNationalId()->getNumber());
            $institution->getNationalId()->setIdType($institutionType->getNationalId()->getType());
        }

        if ($institutionType->getAddress() !== null) {
            $addressType = $institutionType->getAddress();
            $location = $this->locationService->getLocationByInputType($country, $addressType->getLocation());

            $institution->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
                ));
        }

        foreach ($institutionType->getProjects() as $projectId) {
            $project = $this->em->getRepository(Project::class)->find($projectId);
            if (null === $project) {
                throw new \InvalidArgumentException("Project $projectId doesn't exist");
            }
            $institution->addProject($project);
        }

        return $institution;
    }

    public function remove(Institution $institution)
    {
        $institution->setArchived(true);
        $this->em->persist($institution);
        $this->em->flush();

        return $institution;
    }

    public function removeMany(array $institutionIds)
    {
        foreach ($institutionIds as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);
            $institution->setArchived(true);
            $this->em->persist($institution);
        }
        $this->em->flush();
        return "Institutions have been archived";
    }


    /**
     * @return mixed
     */
    public function exportToCsv()
    {
        $exportableTable = $this->em->getRepository(Institution::class)->findAll();
        return  $this->container->get('export_csv_service')->export($exportableTable);
    }

    /**
     * @param array $institutionsArray
     * @return array
     */
    public function getAllImported(array $institutionsArray)
    {
        $institutionsId = $institutionsArray['institutions'];

        $institutions = array();

        foreach ($institutionsId as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);

            if ($institution instanceof Institution) {
                array_push($institutions, $institution);
            }
        }

        return $institutions;
    }

    /**
     * @param GlobalInputType\Country         $iso3
     * @param Institution                     $institution
     * @param InputType\UpdateInstitutionType $institutionType
     *
     * @return Institution
     * @throws \InvalidArgumentException
     */
    public function update(GlobalInputType\Country $iso3, Institution $institution, InputType\UpdateInstitutionType $institutionType): Institution
    {
        if ($institution->getContact() == null) {
            $institution->setContact(new Person());
        }
        if (null !== $newValue = $institutionType->getName()) {
            $institution->setName($newValue);
        }
        if (null !== $newValue = $institutionType->getLongitude()) {
            $institution->setLongitude($newValue);
        }
        if (null !== $newValue = $institutionType->getLatitude()) {
            $institution->setLatitude($newValue);
        }
        if (null !== $newValue = $institutionType->getType()) {
            $institution->setType($newValue);
        }

        if ($institutionType->getNationalId() !== null) {
            if ($institution->getNationalId() == null) {
                $institution->setNationalId(new NationalId());
            }
            $institution->getNationalId()->setIdType($institutionType->getNationalId()->getType());
            $institution->getNationalId()->setIdNumber($institutionType->getNationalId()->getNumber());
        }
        if (null !== $newValue = $institutionType->getContactName()) {
            $institution->setContactName($newValue);
        }
        if (null !== $newValue = $institutionType->getContactFamilyName()) {
            $institution->setContactFamilyName($newValue);
        }
        if (null !== $newNumber = $institutionType->getPhoneNumber()) {
            $newPrefix = $institutionType->getPhonePrefix();
            if ($institution->getPhone() == null) {
                $institution->setPhone(new Phone());
            }
            $institution->getPhone()->setPrefix($newPrefix);
            $institution->getPhone()->setNumber($newNumber);
        }

        /** @var InputType\BeneficiaryAddressType $address */
        if (null !== $address = $institutionType->getAddress()) {
            $location = null;
            if ($address->getLocation() !== null) {
                $location = $this->locationService->getLocationByInputType($iso3, $address->getLocation());
            }
            $this->updateAddress($institution, Address::create(
                $address->getStreet(),
                $address->getNumber(),
                $address->getPostcode(),
                $location
                ));
        }

        if (null !== $institutionType->getProjects()) {
            $institution->setProjects(new ArrayCollection());
            foreach ($institutionType->getProjects() as $projectId) {
                $project = $this->em->getRepository(Project::class)->find($projectId);
                if (null === $project) {
                    throw new \InvalidArgumentException("Project $projectId doesn't exist");
                }
                $institution->addProject($project);
            }
        }

        return $institution;
    }

    private function updateAddress(Institution $institution, Address $newAddress)
    {
        if (null === $institution->getAddress()) {
            $institution->setAddress($newAddress);
            return;
        }
        if (! $institution->getAddress()->equals($newAddress)) {
            $this->em->remove($institution->getAddress());
            $institution->setAddress($newAddress);
        }
    }
}
