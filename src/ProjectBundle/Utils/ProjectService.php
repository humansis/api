<?php

namespace ProjectBundle\Utils;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\ProjectBeneficiary;
use dateTime;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Project;
use ProjectBundle\Entity\Sector;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProject;

/**
 * Class ProjectService
 * @package ProjectBundle\Utils
 */
class ProjectService
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * ProjectService constructor.
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
     * Get all projects
     *
     * @param $countryIso3
     * @param User $user
     * @return array
     */
    public function findAll($countryIso3, User $user)
    {
        if (
            !$user->hasRole("ROLE_COUNTRY_MANAGER")
            && !$user->hasRole("ROLE_REGIONAL_MANAGER")
            && !$user->hasRole("ROLE_ADMIN")
        ) {
            $projects = $this->em->getRepository(Project::class)->getAllOfUser($user);
        } else {
            $projects = $this->em->getRepository(Project::class)->getAllOfCountry($countryIso3);
        }
        return $projects;
    }

    /**
     * @param string $iso3
     * @return mixed
     */
    public function countAll(string $iso3)
    {
        $count = $this->em->getRepository(Project::class)->count(['iso3' => $iso3, 'archived' => 0]);
        return $count;
    }

    /**
     * Create a project
     *
     * @param $countryISO3
     * @param array $projectArray
     * @param User $user
     * @return Project
     * @throws \Exception
     */
    public function create($countryISO3, array $projectArray, User $user)
    {
        /** @var Project $project */

        $startDate = DateTime::createFromFormat('d-m-Y', $projectArray["start_date"]);
        $endDate = DateTime::createFromFormat('d-m-Y', $projectArray["end_date"]);

        if ($startDate > $endDate) {
            throw new \Exception('The end date must be after the start date', Response::HTTP_BAD_REQUEST);
        }

        $project = new Project();
        $project->setName($projectArray["name"])
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setIso3($countryISO3)
                ->setTarget($projectArray["target"])
                ->setNotes($projectArray["notes"]);

        if (isset($projectArray["internal_id"])) {
            $project->setInternalId($projectArray["internal_id"]);
        }

        $existingProject = $this->em->getRepository(Project::class)->findBy(
            [
                'name' => $project->getName(),
                'iso3' => $project->getIso3(),
            ]
        );
        if (!empty($existingProject)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'Project with the name '.$project->getName().' already exists');
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $sectorsId = $projectArray["sectors"];
        if (null !== $sectorsId) {
            $project->getSectors()->clear();
            /** @var Sector $sector */
            foreach ($sectorsId as $sectorId) {
                $sectorTmp = $this->em->getRepository(Sector::class)->find($sectorId);
                if ($sectorTmp instanceof Sector) {
                    $project->addSector($sectorTmp);
                }
            }
        }

        $donorsId = $projectArray["donors"];
        if (null !== $donorsId) {
            $project->getDonors()->clear();
            /** @var Donor $donor */
            foreach ($donorsId as $donorId) {
                $donorTmp = $this->em->getRepository(Donor::class)->find($donorId);
                if ($donorTmp instanceof Donor) {
                    $project->addDonor($donorTmp);
                }
            }
        }

        $this->em->persist($project);
        $this->em->flush();

        $this->addUser($project, $user);

        return $project;
    }

    /**
     * @param Project $project
     * @param array $projectArray
     * @return array|bool|Project
     * @throws \Exception
     */
    public function edit(Project $project, array $projectArray)
    {
        $startDate = DateTime::createFromFormat('d-m-Y', $projectArray["start_date"]);
        $endDate = DateTime::createFromFormat('d-m-Y', $projectArray["end_date"]);

        if ($startDate > $endDate) {
            throw new \Exception('The end date must be after the start date', Response::HTTP_BAD_REQUEST);
        }
    
        /** @var Project $editedProject */
        $oldProject = $this->em->getRepository(Project::class)->find($project->getId());
        if ($oldProject->getArchived() == 0) {
            $project->setName($projectArray['name'])
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setTarget($projectArray['target'])
                ->setNotes($projectArray["notes"]);

            if (isset($projectArray["internal_id"])) {
                $project->setInternalId($projectArray["internal_id"]);
            }

            $sectors = $projectArray['sectors'];
            if (null !== $sectors) {
                $project->removeSectors();
                foreach ($sectors as $sector) {
                    $newSector = $this->em->getRepository(Sector::class)->find($sector);
                    if ($newSector instanceof Sector) {
                        $project->addSector($newSector);
                    }
                }
            }

            $donors = $projectArray['donors'];

            if (null !== $donors) {
                $project->removeDonors();
                /** @var Donor $donor */
                foreach ($donors as $donor) {
                    $newDonor = $this->em->getRepository(Donor::class)->find($donor);
                    if ($newDonor instanceof Donor) {
                        $project->addDonor($newDonor);
                    }
                }
            }

            $errors = $this->validator->validate($project);
            if (count($errors) > 0) {
                $errorsArray = [];
                foreach ($errors as $error) {
                    $errorsArray[] = $error->getMessage();
                }
                throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
            }

            $this->em->merge($project);
            try {
                $this->em->flush();
            } catch (\Exception $e) {
                return false;
            }

            return $project;
        } else {
            return ['error' => 'The project is archived'];
        }
    }

    /**
     * Add multiple households to project.
     *
     * @param Project $project
     * @param $households
     * @return array
     */
    public function addMultipleHouseholds(Project $project, $households)
    {
        foreach ($households as $hh) {
            if (!$hh instanceof Household) {
                $hh = $this->em->getRepository(Household::class)->find($hh['id']);
            }

            $projectHousehold = $hh->getProjects()->contains($project);
            if (!$projectHousehold) {
                $hh->addProject($project);
            }
            $this->em->persist($hh);
        }
        $this->em->persist($project);
        $this->em->flush();

        return $households ;
    }

    /**
     * @param Project $project
     * @param User $user
     */
    public function addUser(Project $project, User $user)
    {
        $right = $user->getRoles();
        if ($right[0] !== "ROLE_ADMIN") {
            $userProject = new UserProject();
            $userProject->setUser($user)
                ->setProject($project)
                ->setRights($right[0]);
    
            $this->em->persist($userProject);
            $this->em->flush();
        }
    }

    /**
     * @param Project $project
     * @return void
     * @throws error if one or more distributions prevent the project from being deleted
     */
    public function delete(Project $project)
    {
        $distributionData = $this->em->getRepository(DistributionData::class)->findByProject($project);

        if (empty($distributionData)) {
            try {
                $this->em->remove($project);
            } catch (\Exception $error) {
                throw new \Exception("Error deleting project");
            }
        } else {
            if (! $this->checkIfAllDistributionClosed($distributionData)) {
                throw new \Exception("You can't delete this project as it has an unfinished distribution");
            } else {
                try {
                    foreach ($distributionData as $distributionDatum) {
                        $distributionDatum->setArchived(1);
                    }

                    $project->setArchived(true);
                    $this->em->persist($project);
                } catch (\Exception $error) {
                    throw new \Exception("Error archiving project");
                }
            }
        }
        $this->em->flush();
    }

    /**
     * Check if all distributions allow for the project to be deleted
     * @param DistributionData $distributionData
     * @return boolean
     */
    private function checkIfAllDistributionClosed(array $distributionData)
    {
        foreach ($distributionData as $distributionDatum) {
            if (!$distributionDatum->getArchived() && !$distributionDatum->getCompleted()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Export all projects of the country in the CSV file
     * @param $countryIso3
     * @param string $type
     * @return mixed
     */
    public function exportToCsv($countryIso3, string $type)
    {
        $exportableTable = $this->em->getRepository(Project::class)->getAllOfCountry($countryIso3);
        return $this->container->get('export_csv_service')->export($exportableTable, 'projects', $type);
    }
}
