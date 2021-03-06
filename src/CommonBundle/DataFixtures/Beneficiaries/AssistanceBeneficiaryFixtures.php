<?php

namespace CommonBundle\DataFixtures\Beneficiaries;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use CommonBundle\DataFixtures\AssistanceFixtures;
use CommonBundle\DataFixtures\BeneficiaryTestFixtures;
use CommonBundle\DataFixtures\ProjectFixtures;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceBeneficiaryFixtures extends Fixture implements DependentFixtureInterface//, FixtureGroupInterface
{
    private $distributionService;

    private $kernel;

    public function __construct(Kernel $kernel, AssistanceService $distributionService)
    {
        $this->distributionService = $distributionService;
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $projects = $manager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            echo $project->getName()."#{$project->getId()}: \n";
            $assistances = $manager->getRepository(Assistance::class)->findBy([
                'project' => $project,
            ]);

            foreach ($assistances as $assistance) {
                echo "P#{$project->getId()} - ".$assistance->getName().": ";
                if ($assistance->getCommodities()[0]->getModalityType()->getName() === 'Smartcard') continue;
                switch ($assistance->getTargetType()) {
                    case AssistanceTargetType::INDIVIDUAL:
                        $this->addBNFsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::HOUSEHOLD:
                        $this->addHHsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::INSTITUTION:
                        $this->addInstsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::COMMUNITY:
                        $this->addCommsToAssistance($manager, $assistance, $project);
                        break;
                }
                $manager->persist($assistance);
                echo "\n";
            }
            $manager->persist($project);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            AssistanceFixtures::class,
            BeneficiaryTestFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    private function addBNFsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $BNFs = $manager->getRepository(Beneficiary::class)->getUnarchivedByProject($project);
        echo "(".count($BNFs).") ";
        foreach ($BNFs as $beneficiary) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($beneficiary)
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "B";
        }
    }

    private function addHHsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $HHs = $manager->getRepository(Household::class)->getUnarchivedByProject($project)->getQuery()->getResult();
        echo "(".count($HHs).") ";
        /** @var Household $household */
        foreach ($HHs as $household) {
            if (!$household->getHouseholdHead()) {
                echo 'h';
                continue;
            }
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($household->getHouseholdHead())
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "H";
        }
    }

    private function addInstsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $institutions = $manager->getRepository(Institution::class)->getUnarchivedByProject($project);
        echo "(".count($institutions).") ";
        foreach ($institutions as $institution) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($institution)
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "I";
        }
    }

    private function addCommsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $communities = $manager->getRepository(Community::class)->getUnarchivedByProject($project)->getQuery()->getResult();
        echo "(".count($communities).") ";
        foreach ($communities as $community) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($community)
                ->setAssistance($assistance)
                ->setRemoved(false)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "C";
        }
    }
}
