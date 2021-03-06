<?php

namespace DistributionBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Location;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\Query\Expr\Join;
use \DateTime;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\AssistanceByProjectOfflineAppFilterInputType;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\InputType\ProjectsAssistanceFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;

/**
 * AssistanceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AssistanceRepository extends \Doctrine\ORM\EntityRepository
{
    public function getLastId()
    {
        $qb = $this->createQueryBuilder('dd')
                   ->select("MAX(dd.id)");
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalValue(string $country)
    {
        $qb = $this->createQueryBuilder("dd");

        $qb
            ->select("SUM(c.value)")
            ->leftJoin("dd.project", "p")
            ->where("p.iso3 = :country")
                ->setParameter("country", $country)
            ->leftJoin("dd.commodities", "c")
            ->leftJoin("c.modalityType", "mt")
            ->andWhere("mt.name = 'Mobile'");

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getActiveByCountry(string $country)
    {
        $qb = $this->createQueryBuilder("dd")
                    ->leftJoin("dd.project", "p")
                    ->where("p.iso3 = :country")
                    ->setParameter("country", $country)
                    ->andWhere("dd.archived = 0");
        return $qb->getQuery()->getResult();
    }

    public function getCodeOfUpcomingDistribution(string $countryISO)
    {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->addSelect('p')
            ->addSelect('l')
            ->addSelect('adm1')
            ->addSelect('adm2')
            ->addSelect('adm3')
            ->addSelect('adm4')
            ->innerJoin('dd.project', 'p')
            ->innerJoin('dd.location', 'l')
            ->leftJoin('l.adm1', 'adm1')
            ->leftJoin('l.adm2', 'adm2')
            ->leftJoin('l.adm3', 'adm3')
            ->leftJoin('l.adm4', 'adm4')
            ->andWhere('p.iso3 = :country')
                ->setParameter('country', $countryISO)
            ->andWhere('dd.dateDistribution > :now')
                ->setParameter('now', new DateTime());

        return $qb->getQuery()->getResult();
    }

    public function countCompleted(string $countryISO3) {
        $qb = $this->createQueryBuilder('dd');
        $qb->select('COUNT(dd)')
            ->leftJoin("dd.location", "l");
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
        $qb->andWhere("dd.completed = 1");

        return $qb->getQuery()->getSingleScalarResult();

    }

    public function getNoBenificiaryByResidencyStatus(int $distributionId, string $residencyStatus, string $distributionType) {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
            ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0');
        if ($distributionType === AssistanceTargetType::INDIVIDUAL) {
            $qb->leftJoin('db.beneficiary', 'b', Join::WITH, 'b.residencyStatus = :residencyStatus');
        } else {
            $qb->leftJoin('db.beneficiary', 'hhh')
                ->leftJoin('hhh.household', 'hh')
                ->leftJoin('hh.beneficiaries', 'b', Join::WITH, 'b.residencyStatus = :residencyStatus');
        }
           $qb->setParameter('residencyStatus', $residencyStatus)
                ->select('COUNT(b)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNoHeadHouseholdsByGender(int $distributionId, int $gender) {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
            ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0')
            ->leftJoin('db.beneficiary', 'b', Join::WITH, 'b.gender = :gender AND b.status = 1')
                ->setParameter('gender', $gender)
            ->select('COUNT(b)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNoFamilies(int $distributionId) {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
            ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0')
            ->leftJoin('db.beneficiary', 'b')
            ->leftJoin('b.household', 'hh')
            ->select('COUNT(DISTINCT hh)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNoBenificiaryByAgeAndByGender(int $distributionId, int $gender, int $minAge, int $maxAge, DateTime $distributionDate, string $distributionType) {
        $maxDateOfBirth = clone $distributionDate;
        $minDateOfBirth = clone $distributionDate;
        $maxDateOfBirth->sub(new \DateInterval('P'.$minAge.'Y'));
        $minDateOfBirth->sub(new \DateInterval('P'.$maxAge.'Y'));
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
            ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0');

        if ($distributionType === AssistanceTargetType::INDIVIDUAL) {
            $qb->leftJoin('db.beneficiary', 'b', Join::WITH, 'b.dateOfBirth >= :minDateOfBirth AND b.dateOfBirth < :maxDateOfBirth AND b.gender = :gender');
        } else {
            $qb->leftJoin('db.beneficiary', 'hhh')
                ->leftJoin('hhh.household', 'hh')
                ->leftJoin('hh.beneficiaries', 'b', Join::WITH, 'b.dateOfBirth >= :minDateOfBirth AND b.dateOfBirth < :maxDateOfBirth AND b.gender = :gender');
        }
                $qb->setParameter('minDateOfBirth', $minDateOfBirth)
                ->setParameter('maxDateOfBirth', $maxDateOfBirth)
                ->setParameter('gender', $gender)
            ->select('COUNT(DISTINCT b)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNoServed(int $distributionId, string $modalityType) {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
                ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0')
                ->select('COUNT(DISTINCT db)');

                if ($modalityType === 'Mobile Money') {
                    $qb->innerJoin('db.transactions', 't', Join::WITH, 't.transactionStatus = 1');
                } else if ($modalityType === 'QR Code Voucher') {
                    $qb->innerJoin('db.booklets', 'b', Join::WITH, 'b.status = 1 OR b.status = 2');
                } else {
                    $qb->innerJoin('db.generalReliefs', 'gr', Join::WITH, 'gr.distributedAt IS NOT NULL');
                }
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns list of distributions distributed to given beneficiary
     *
     * @param Beneficiary $beneficiary
     * @return Assistance[]
     */
    public function findDistributedToBeneficiary(Beneficiary $beneficiary)
    {
        $qb = $this->createQueryBuilder('dd')
            ->join('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.beneficiary = :beneficiary')
            ->orderBy('dd.dateDistribution', 'DESC');

        $qb->setParameter('beneficiary', $beneficiary);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Project|null                           $project
     * @param string|null                            $iso3
     * @param ProjectsAssistanceFilterInputType|null $filter
     * @param AssistanceOrderInputType|null          $orderBy
     * @param Pagination|null                        $pagination
     *
     * @return Paginator|Assistance[]
     */
    public function findByProject(
        Project $project,
        ?string $iso3 = null,
        ?ProjectsAssistanceFilterInputType $filter = null,
        ?AssistanceOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator
    {
        $qb = $this->createQueryBuilder('dd')
            ->andWhere('dd.archived = 0')
            ->andWhere('dd.project = :project')
            ->setParameter('project', $project);

        if ($iso3) {
            $qb->leftJoin('dd.project', 'p')
                ->andWhere('p.iso3 = :iso3')
                ->setParameter('iso3', $iso3);
        }

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->andWhere('(dd.id = :id OR
                               dd.name LIKE :fulltext OR
                               dd.description LIKE :fulltext)')
                    ->setParameter('id', $filter->getFulltext())
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case AssistanceOrderInputType::SORT_BY_ID:
                        $qb->orderBy('dd.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_LOCATION:
                        $qb->leftJoin('dd.location', 'l');
                        $qb->orderBy('l.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_DATE:
                        $qb->orderBy('dd.dateDistribution', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('dd.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TARGET:
                        $qb->orderBy('dd.targetType', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NUMBER_OF_BENEFICIARIES:
                        $qb->orderBy('SIZE(dd.distributionBeneficiaries)', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('dd.assistanceType', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    /**
     * @param Project                                      $project
     * @param string                                       $iso3
     * @param AssistanceByProjectOfflineAppFilterInputType $filter
     *
     * @return Assistance[]
     */
    public function findByProjectInOfflineApp(
        Project $project,
        string $iso3,
        AssistanceByProjectOfflineAppFilterInputType $filter
    ): iterable
    {
        $qbr = $this->createQueryBuilder('dd')
            ->leftJoin('dd.project', 'p')
            ->andWhere('dd.archived = 0')
            ->andWhere('dd.validated = 1')
            ->andWhere('dd.project = :project')
            ->andWhere('p.iso3 = :iso3')
            ->setParameter('project', $project)
            ->setParameter('iso3', $iso3);

        if ($filter->hasType()) {
            $qbr->andWhere('dd.assistanceType = :type')
                ->setParameter('type', $filter->getType());
        }

        if ($filter->hasCompleted()) {
            $qbr->andWhere('dd.completed = :completed')
                ->setParameter('completed', $filter->getCompleted());
        }

        if ($filter->hasModalityTypes()) {
            $qbr->join('dd.commodities', 'c')
                ->join('c.modalityType', 'm', 'WITH', 'm.name IN (:modalityTypes)')
                ->setParameter('modalityTypes', $filter->getModalityTypes());
        }

        return $qbr->getQuery()->getResult();
    }

    /**
     * @param string                         $iso3
     * @param AssistanceFilterInputType|null $filter
     * @param AssistanceOrderInputType|null  $orderBy
     * @param Pagination|null                $pagination
     *
     * @return Paginator|Assistance[]
     */
    public function findByParams(
        string $iso3,
        ?AssistanceFilterInputType $filter = null,
        ?AssistanceOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('dd')
            ->andWhere('dd.archived = 0');

        if ($iso3) {
            $qb->leftJoin('dd.project', 'p')
                ->andWhere('p.iso3 = :iso3')
                ->setParameter('iso3', $iso3);
        }

        if (($filter && $filter->hasModalityTypes()) ||
            ($orderBy && (
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_MODALITY_TYPE) ||
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_UNIT) ||
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_VALUE)))) {
            $qb->leftJoin('dd.commodities', 'c');

            if ($filter->hasModalityTypes() || $orderBy->has(AssistanceOrderInputType::SORT_BY_MODALITY_TYPE)) {
                $qb->leftJoin('c.modalityType', 'mt');
            }
        }

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('dd.id IN (:ids)')
                    ->setParameter('ids', $filter->getIds());
            }
            if ($filter->hasUpcomingOnly() && $filter->getUpcomingOnly()) {
                $qb->andWhere('p.startDate > :now')
                    ->setParameter('now', new DateTime('now'));
            }
            if ($filter->hasType()) {
                $qb->andWhere('dd.assistanceType = :assistanceType')
                    ->setParameter('assistanceType', $filter->getType());
            }
            if ($filter->hasProjects()) {
                $qb->andWhere('dd.project IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
            if ($filter->hasLocations()) {
                $this->createQueryBuilder('l')
                    ->andWhere('dd.location IN (:locations)')
                    ->setParameter('locations', $filter->getLocations());
            }
            if ($filter->hasModalityTypes()) {
                $qb->andWhere('mt.name IN (:modalityTypes)')
                    ->setParameter('modalityTypes', $filter->getModalityTypes());
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case AssistanceOrderInputType::SORT_BY_ID:
                        $qb->orderBy('dd.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_LOCATION:
                        $qb->leftJoin('dd.location', 'l');
                        $qb->orderBy('l.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_DATE:
                        $qb->orderBy('dd.dateDistribution', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('dd.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TARGET:
                        $qb->orderBy('dd.targetType', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NUMBER_OF_BENEFICIARIES:
                        $qb->orderBy('SIZE(dd.distributionBeneficiaries)', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_PROJECT:
                        $qb->leftJoin('dd.project', 'p')
                            ->orderBy('p.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_MODALITY_TYPE:
                        $qb->orderBy('mt.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_VALUE:
                        $qb->orderBy('c.value', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_UNIT:
                        $qb->orderBy('c.unit', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('dd.assistanceType', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }
}
