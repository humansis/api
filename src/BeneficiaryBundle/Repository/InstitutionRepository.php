<?php

declare(strict_types=1);

namespace BeneficiaryBundle\Repository;

use CommonBundle\Entity\Location;
use CommonBundle\InputType\Country;
use CommonBundle\InputType\DataTableFilterType;
use CommonBundle\InputType\DataTableSorterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * InstitutionRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InstitutionRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get all Institution by country.
     *
     * @param Country                  $country
     * @param                          $begin
     * @param                          $pageSize
     * @param DataTableSorterType|null $sort
     * @param DataTableFilterType[]    $filters
     *
     * @return mixed
     */
    public function getAllBy(Country $country, $begin, $pageSize, DataTableSorterType $sort = null, array $filters = [])
    {
        // Recover global information for the page
        $qb = $this->createQueryBuilder('inst');

        // We join information that is needed for the filters
        $q = $qb->andWhere('inst.archived = 0');

        $this->whereInstitutionInCountry($q, $country->getIso3());

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
                    $q->join('inst.projects', $projectAlias);
                    foreach ($values as $value) {
                        $q->orWhere("$projectAlias.name LIKE :projectName$filterIndex");
                        $q->setParameter('projectName'.$filterIndex, $value);
                        ++$filterIndex;
                    }
                    break;
                case 'name':
                    foreach ($values as $value) {
                        $q->andWhere('inst.name LIKE :name'.$filterIndex);
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
        $query->useResultCache(true, 3600);

        return [count($paginator), $query->getResult()];
    }

    protected function getInstitutionLocation(QueryBuilder &$qb)
    {
        $qb->leftJoin('inst.address', 'addr');
        $qb->leftJoin('addr.location', 'l');
    }

    public function whereInstitutionInCountry(QueryBuilder &$qb, $countryISO3)
    {
        $this->getInstitutionLocation($qb);
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
    }
}
