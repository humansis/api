<?php

namespace BeneficiaryBundle\Repository;

use BeneficiaryBundle\Entity\CommunityLocation;
use CommonBundle\Entity\Location;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;

/**
 * CommunityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CommunityRepository extends \Doctrine\ORM\EntityRepository
{
    /**
    * Find all communitys in country
    * @param  string $iso3
    * @return QueryBuilder
    */
    public function findAllByCountry(string $iso3)
    {
        $qb = $this->createQueryBuilder("comm");
        $this->whereCommunityInCountry($qb, $iso3);
        $qb->andWhere('comm.archived = 0');

        return $qb;
    }

    public function getUnarchivedByProject(Project $project)
    {
        $qb = $this->createQueryBuilder("comm");
        $q = $qb->leftJoin("comm.projects", "p")
            ->where("p = :project")
            ->setParameter("project", $project)
            ->andWhere("comm.archived = 0");

        return $q;
    }

    public function countUnarchivedByProject(Project $project)
    {
        $qb = $this
            ->createQueryBuilder("comm")
            ->select("COUNT(comm)")
            ->leftJoin("comm.projects", "p")
            ->where("p = :project")
            ->setParameter("project", $project)
            ->andWhere("comm.archived = 0");

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Return communitys which a Levenshtein distance with the stringToSearch under minimumTolerance
     * @param string $iso3
     * @param string $stringToSearch
     * @param int $minimumTolerance
     * @return mixed
     */
    public function foundSimilarAddressLevenshtein(string $iso3, string $stringToSearch, int $minimumTolerance)
    {
        $qb = $this->findAllByCountry($iso3);
        $q = $qb->leftJoin("comm.beneficiaries", "b")
            // Those leftJoins are already done in findAllByCountry
            // ->leftJoin("comm.location", "hl")
            // ->leftJoin("hl.address", "ad")
            ->select("comm as community")
            ->andWhere("comm.archived = 0")
            ->addSelect(
                "LEVENSHTEIN(
                    CONCAT(
                        COALESCE(ad.street, ''),
                        COALESCE(ad.number, ''),
                        COALESCE(ad.postcode, ''),
                        COALESCE(b.localGivenName, ''),
                        COALESCE(b.localFamilyName, '')
                    ),
                    :stringToSearch
                ) as levenshtein")
            ->groupBy("b, ad")
            ->having("levenshtein <= :minimumTolerance")
            ->setParameter("stringToSearch", $stringToSearch)
            ->setParameter("minimumTolerance", $minimumTolerance)
            ->orderBy("levenshtein", "ASC");

        return $q->getQuery()->getResult();
    }

    /**
     * Get all Community by country
     * @param $iso3
     * @param $begin
     * @param $pageSize
     * @param $sort
     * @param array $filters
     * @return mixed
     */
    public function getAllBy(string $iso3, $begin, $pageSize, $sort, $filters = [])
    {
        // Recover global information for the page
        $qb = $this->createQueryBuilder("comm");

        // We join information that is needed for the filters
        $q = $qb->andWhere("comm.archived = 0");

        $this->whereCommunityInCountry($q, $iso3);

        $filterIndex = 0;
        foreach ($filters as $filter) {
            if (is_array($filter['filter'])) {
                $values = $filter['filter'];
            } else {
                $values = [$filter['filter']];
            }
            switch ($filter['category']) {
                case 'projectName':
                    $projectAlias = "project$filterIndex";
                    $q->join('comm.projects', $projectAlias);
                    foreach ($values as $value) {
                        $q->orWhere("$projectAlias.name LIKE :projectName$filterIndex");
                        $q->setParameter('projectName'.$filterIndex, $value);
                        ++$filterIndex;
                    }
                    break;
                case 'name':
                    foreach ($values as $value) {
                        $q->andWhere('comm.name LIKE :name'.$filterIndex);
                        $q->setParameter('name'.$filterIndex, $value);
                        ++$filterIndex;
                    }
                    break;
            }
            ++$filterIndex;
        }

        if (is_null($begin)) {
            $begin = 0;
        }
        if (is_null($pageSize)) {
            $pageSize = 0;
        }

        if ($pageSize > -1) {
            $q->setFirstResult($begin)
                ->setMaxResults($pageSize);
        }

        $paginator = new Paginator($q, true);

        $query = $q->getQuery();

        return [count($paginator), $query->getResult()];
    }

    /**
     * Get all Community by country and id
     * @param string $iso3
     * @param array  $ids
     * @return mixed
     */
    public function getAllByIds(array $ids)
    {
        $qb = $this
            ->createQueryBuilder("comm")
            ->addSelect(['beneficiaries', 'projects', 'location', 'specificAnswers'])
            ->leftJoin('comm.beneficiaries', 'beneficiaries')
            ->leftJoin('comm.projects', 'projects')
            ->leftJoin('comm.location', 'location')
            ->leftJoin('comm.countrySpecificAnswers', 'specificAnswers')
            ->andWhere('comm.archived = 0')
            ->andWhere('comm.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     *
     */
    public function getByHeadAndLocation(
        string $givenName,
        string $familyName,
        string $locationType,
        string $street = null,
        string $number = null,
        string $tentNumber = null
    ) {
        $qb = $this->createQueryBuilder('comm')
            ->select('comm')
            ->innerJoin('comm.beneficiaries', 'b')
            ->innerJoin('comm.location', 'hl')
            ->where('comm.archived = 0')
            ->andWhere('b.localGivenName = :givenName')
            ->setParameter('givenName', $givenName)
            ->andWhere('b.localFamilyName = :familyName')
            ->setParameter('familyName', $familyName)
        ;

        $qb
            ->leftJoin('hl.address', 'ad')
            ->andWhere('ad.street = :street')
            ->setParameter('street', $street)
            ->andWhere('ad.number = :number')
            ->setParameter('number', $number)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Create sub request to get location from community
     *
     * @param QueryBuilder $qb
     */
    protected function getCommunityLocation(QueryBuilder &$qb)
    {
        $qb->leftJoin("comm.address", "addr");
        $qb->leftJoin("addr.location", "l");
    }

    /**
     * Create sub request to get communitys in country.
     * The community address location must be in the country ($countryISO3).
     *
     * @param QueryBuilder $qb
     * @param $countryISO3
     */
    public function whereCommunityInCountry(QueryBuilder &$qb, $countryISO3)
    {
        $this->getCommunityLocation($qb);
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
    }

    /**
     * @param string $countryIso3
     * @param CommunityFilterType|null $filter
     * @param Pagination|null $pagination
     *
     * @return Paginator
     */
    public function findByParams(string $countryIso3, ?CommunityFilterType $filter, ?Pagination $pagination = null): Paginator
    {
        // Recover global information for the page
        $qb = $this->createQueryBuilder("comm");

        // We join information that is needed for the filters
        $q = $qb->andWhere("comm.archived = 0");

        $this->whereCommunityInCountry($q, $countryIso3);

        $filterIndex = 0;

        if (!is_null($filter)) {
            if ($filter->hasFulltext()) {
                // TODO
            }
            // filter per names in array
            if ($filter->hasName()) {
                foreach ($filter->getName() as $value) {
                    $q->andWhere('comm.name LIKE :name'.$filterIndex);
                    $q->setParameter('name'.$filterIndex, $value);
                    ++$filterIndex;
                }
            }
            // filter per project names in array
            if ($filter->hasProjectName()) {
                $projectAlias = "project$filterIndex";
                $q->join('comm.projects', $projectAlias);
                foreach ($filter->getProjectName() as $value) {
                    $q->orWhere("$projectAlias.name LIKE :projectName$filterIndex");
                    $q->setParameter('projectName'.$filterIndex, $value);
                    ++$filterIndex;
                }
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }


        return new Paginator($qb);
    }
}
