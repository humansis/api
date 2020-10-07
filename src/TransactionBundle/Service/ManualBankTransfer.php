<?php

namespace TransactionBundle\Service;

use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Service\Exception\TransactionException;

class ManualBankTransfer
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param DistributionBeneficiary[] $distributionBeneficiaries
     */
    public function setAsDistributed(iterable $distributionBeneficiaries)
    {
        $alreadyDistributed = [];
        $invalidCommodities = [];

        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $commodity = $this->getCommodity($distributionBeneficiary);
            if (null === $commodity) {
                $invalidCommodities[] = $distributionBeneficiary;
                continue;
            }

            if (count($distributionBeneficiary->getTransactions()) > 0) {
                $alreadyDistributed[] = $distributionBeneficiary;
                continue;
            }

            $transaction = $this->createTransaction($distributionBeneficiary, $commodity->getValue());
            $this->em->persist($transaction);
        }

        // error handling
        if (count($alreadyDistributed) > 0) {
            $this->em->clear();
            throw $this->createAlreadyDistributedException($alreadyDistributed);
        }

        if (count($invalidCommodities) > 0) {
            $this->em->clear();
            throw $this->createInvalidCommodityException($invalidCommodities);
        }

        $this->em->flush();
    }

    /**
     * @param DistributionBeneficiary[] $distributionBeneficiaries
     */
    public function setAsPickedUp(iterable $distributionBeneficiaries)
    {
        $notDistributed = [];
        $alreadyPickedUp = [];

        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $transactions = $distributionBeneficiary->getTransactions();

            if (0 === count($transactions)) {
                $notDistributed[] = $distributionBeneficiary;
                continue;
            }

            /** @var Transaction $transaction */
            $transactionArray = $transactions->toArray();
            $transaction = reset($transactionArray);
            if ($transaction->getPickupDate()) {
                $alreadyPickedUp[] = $distributionBeneficiary;
            }

            $transaction->setPickupDate(new \DateTime('now'));
            $transaction->setMoneyReceived($transaction->getAmountSent());

            $this->em->persist($transaction);
        }

        // error handling
        if (count($notDistributed) > 0) {
            $this->em->clear();
            throw $this->createNotDistributedException($notDistributed);
        }

        if (count($alreadyPickedUp) > 0) {
            $this->em->clear();
            throw $this->createAlreadyPickedException($alreadyPickedUp);
        }

        $this->em->flush();
    }

    /**
     * Creates transaction entity.
     *
     * @param DistributionBeneficiary $distributionBeneficiary
     * @param                         $amountSent
     *
     * @return Transaction
     */
    protected function createTransaction(DistributionBeneficiary $distributionBeneficiary, $amountSent)
    {
        $transaction = new Transaction();
        $transaction->setTransactionId('');
        $transaction->setDistributionBeneficiary($distributionBeneficiary);
        $transaction->setDateSent(new \DateTime('now'));
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus(1);

        $distributionBeneficiary->addTransaction($transaction);

        return $transaction;
    }

    /**
     * Returns Manual Bank Transfer commodity, if exists.
     *
     * @param DistributionBeneficiary $distributionBeneficiary
     *
     * @return Commodity|null
     */
    private function getCommodity(DistributionBeneficiary $distributionBeneficiary): ?Commodity
    {
        /** @var Commodity $commodity */
        foreach ($distributionBeneficiary->getAssistance()->getCommodities() as $commodity) {
            if ('Manual Bank Transfer' === $commodity->getModalityType()->getName()) {
                return $commodity;
            }
        }

        return null;
    }

    /**
     * @param DistributionBeneficiary[] $alreadyDistributed
     *
     * @return TransactionException
     */
    private function createAlreadyDistributedException(array $alreadyDistributed): TransactionException
    {
        $errorIds = array_map(function ($object) {
            return $object->getBeneficiary()->getId();
        }, $alreadyDistributed);

        if (1 === count($errorIds)) {
            return new TransactionException('Transaction is already distributed to beneficiary with ID '.reset($errorIds));
        }

        return new TransactionException('Transactions are already distributed to beneficiaries with IDs '.implode(', ', $errorIds));
    }

    /**
     * @param DistributionBeneficiary[] $invalidCommodities
     *
     * @return TransactionException
     */
    private function createInvalidCommodityException(array $invalidCommodities): TransactionException
    {
        $errorIds = array_map(function ($object) {
            return $object->getAssistance()->getId();
        }, $invalidCommodities);

        if (1 === count($errorIds)) {
            return new TransactionException('Distribution with ID '.reset($errorIds).' does not provide Manual bank transfer');
        }

        return new TransactionException('Distributions with IDs '.implode(', ', $errorIds).' do not provide Manual bank transfer');
    }

    /**
     * @param DistributionBeneficiary[] $notDistributed
     *
     * @return TransactionException
     */
    private function createNotDistributedException(array $notDistributed): TransactionException
    {
        $errorIds = array_map(function ($object) {
            return $object->getAssistance()->getId();
        }, $notDistributed);

        if (1 === count($errorIds)) {
            return new TransactionException('Distribution with ID '.reset($errorIds).' did not transfer money to beneficiary yet');
        }

        return new TransactionException('Distributions with IDs '.implode(', ', $errorIds).' did not transfer money to beneficiaries yet');
    }

    /**
     * @param DistributionBeneficiary[] $alreadyPickedUp
     *
     * @return TransactionException
     */
    private function createAlreadyPickedException(array $alreadyPickedUp): TransactionException
    {
        $errorIds = array_map(function ($object) {
            return $object->getAssistance()->getId();
        }, $alreadyPickedUp);

        if (1 === count($errorIds)) {
            return new TransactionException('Transaction is already picked up by beneficiary with ID '.reset($errorIds));
        }

        return new TransactionException('Transaction are already picked up by beneficiaries with IDs '.implode(', ', $errorIds));
    }
}
