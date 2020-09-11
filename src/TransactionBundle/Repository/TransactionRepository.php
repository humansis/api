<?php

namespace TransactionBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use TransactionBundle\OutputType\PurchaseCollection;

/**
 * TransactionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionRepository extends \Doctrine\ORM\EntityRepository
{
    public function getPurchases(Beneficiary $beneficiary): PurchaseCollection
    {
        $sql = '
        SELECT spr.product_id, sum(spr.value) as value, sum(spr.quantity) as quantity, \'Smartcard\' as source
            FROM smartcard_purchase_record spr
            LEFT JOIN smartcard_purchase sp ON sp.id=spr.smartcard_purchase_id
            LEFT JOIN smartcard s ON s.id=sp.smartcard_id AND s.beneficiary_id = :beneficiary
            GROUP BY spr.product_id
        UNION
        SELECT vpr.product_id, sum(vpr.value) as value, sum(vpr.quantity) as quantity, \'QRvoucher\' as source
            FROM voucher_purchase_record vpr
            LEFT JOIN voucher_purchase vp ON vp.id=vpr.voucher_purchase_id
            LEFT JOIN voucher v ON v.voucher_purchase_id=vp.id
            LEFT JOIN booklet b ON b.id=v.booklet_id
            LEFT JOIN distribution_beneficiary db ON db.id=b.distribution_beneficiary_id AND db.beneficiary_id = :beneficiary
            GROUP BY vpr.product_id
        ';

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('product_id', 'productId', Types::INTEGER);
        $rsm->addScalarResult('value', 'value', Types::DECIMAL);
        $rsm->addScalarResult('quantity', 'quantity', Types::DECIMAL);
        $rsm->addScalarResult('source', 'source', Types::STRING);

        $query = $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('beneficiary', $beneficiary->getId());

        return new PurchaseCollection($query);
    }
}
