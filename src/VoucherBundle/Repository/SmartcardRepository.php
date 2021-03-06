<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use VoucherBundle\Entity\Smartcard;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    public function findBySerialNumber(string $serialNumber): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->setParameter('serialNumber', strtoupper($serialNumber));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns list of blocked smardcards.
     *
     * @param string $countryCode
     *
     * @return string[] list of smartcard serial numbers
     */
    public function findBlocked(string $countryCode)
    {
        $qb = $this->createQueryBuilder('s')
            ->distinct(true)
            ->select(['s.serialNumber'])
            ->join('s.beneficiary', 'b')
            ->join('b.household', 'h')
            ->join('h.projects', 'p')
            ->andWhere('p.iso3 = :countryCode')
            ->andWhere('s.state != :smartcardState')
            ->setParameter('countryCode', $countryCode)
            ->setParameter('smartcardState', Smartcard::STATE_ACTIVE);

        return $qb->getQuery()->getResult('plain_values_hydrator');
    }
}
