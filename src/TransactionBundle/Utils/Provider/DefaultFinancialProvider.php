<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;

use TransactionBundle\Entity\Transaction;
use TransactionBundle\TransactionBundle;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\DistributionBeneficiary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Class DefaultFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
abstract class DefaultFinancialProvider
{

    /** @var EntityManagerInterface $em */
    protected $em;
    
    /** @var ContainerInterface $container */
    protected $container;

    /** @var string $url */
    protected $url;

    /** @var string from */
    protected $from;

    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }
    
    /**
     * Send request to financial API
     * @param Assistance $assistance
     * @param  string $type    type of the request ("GET", "POST", etc.)
     * @param  string $route   url of the request
     * @param  array  $headers headers of the request (optional)
     * @param  array  $body    body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(Assistance $assistance, string $type, string $route, array $body = array())
    {
        throw new \Exception("You need to define the financial provider for the country.");
    }

    /**
     * Send money to one beneficiary
     * @param  string $phoneNumber
     * @param  DistributionBeneficiary $distributionBeneficiary
     * @param  float $amount
     * @param  string $currency
     * @return void
     * @throws \Exception
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        DistributionBeneficiary $distributionBeneficiary,
        float $amount,
        string $currency
    ) {
        throw new \Exception("You need to define the financial provider for the country.");
    }

    /**
     * Send money to all beneficiaries
     * @param Assistance $assistance
     * @param  float $amount
     * @param  string $currency
     * @param string $from
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendMoneyToAll(Assistance $assistance, float $amount, string $currency, string $from)
    {
        // temporary variables to limit the amount of money that can be sent for one distribution to: 1000$
        $cache = new FilesystemCache();
        if (! $cache->has($assistance->getId() . '-amount_sent')) {
            $cache->set($assistance->getId() . '-amount_sent', 0);
        }

        $this->from = $from;
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['assistance' => $assistance]);

        $response = array(
            'sent'          => array(),
            'failure'       => array(),
            'no_mobile'     => array(),
            'already_sent'  => array()
        );

        $count = 0;
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $cache->set($this->from . '-progression-' . $assistance->getId(), $count);
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            
            $transactions = $distributionBeneficiary->getTransactions();
            if (! $transactions->isEmpty()) {
                // if this beneficiary already has transactions
                // filter out the one that is a success (if it exists)
                $transactions = $transactions->filter(
                    function ($transaction) {
                        return $transaction->getTransactionStatus() === 1;
                    }
                );
            }

            $phoneNumber = null;
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile' || $phone->getType() === 'Mobile') {
                    $phoneNumber = '0' . $phone->getNumber();
                    break;
                }
            }

            if ($phoneNumber) {
                // if a successful transaction already exists
                if (! $transactions->isEmpty()) {
                    array_push($response['already_sent'], $distributionBeneficiary);
                } else {
                    if ($cache->has($assistance->getId() . '-amount_sent')) {
                        $amountSent = $cache->get($assistance->getId() . '-amount_sent');
                    }
                    // if the limit hasn't been reached
                    if (empty($amountSent) || $amountSent + $amount <= 10000) {
                        try {
                            $transaction = $this->sendMoneyToOne($phoneNumber, $distributionBeneficiary, $amount, $currency);
                            if ($transaction->getTransactionStatus() === 0) {
                                array_push($response['failure'], $distributionBeneficiary);
                            } else {
                                // add amount to amount sent
                                $cache->set($assistance->getId() . '-amount_sent', $amountSent + $amount);
                                array_push($response['sent'], $distributionBeneficiary);
                            }
                        } catch (Exception $e) {
                            $this->createTransaction($distributionBeneficiary, '', new \DateTime(), 0, 2, $e->getMessage());
                            array_push($response['failure'], $distributionBeneficiary);
                        }
                    } else {
                        $this->createTransaction($distributionBeneficiary, '', new \DateTime(), 0, 0, "The maximum amount that can be sent per distribution (USD 10000) has been reached");
                    }
                }
            } else {
                $this->createTransaction($distributionBeneficiary, '', new \DateTime(), 0, 2, "No Phone");
                array_push($response['no_mobile'], $distributionBeneficiary);
            }

            $count++;
        }

        $cache->delete($this->from . '-progression-' . $assistance->getId());

        return $response;
    }

    /**
     * Update distribution status (check if money has been picked up)
     * @param  Assistance $assistance
     * @return DistributionBeneficiary[]
     * @throws \Exception
     */
    public function updateStatusDistribution(Assistance $assistance): array
    {
        $response = array();

        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['assistance' => $assistance]);
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $successfulTransaction = $this->em->getRepository(Transaction::class)->findOneBy(
                [
                    'distributionBeneficiary' => $distributionBeneficiary,
                    'transactionStatus'       => 1
                ]
            );
            if ($successfulTransaction) {
                try {
                    $this->updateStatusTransaction($successfulTransaction);
                    array_push($response, $distributionBeneficiary);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        return $response;
    }

    /**
     * Create transaction
     * @param  DistributionBeneficiary $distributionBeneficiary
     * @param  string $transactionId
     * @param \DateTime $dateSent
     * @param string $amountSent
     * @param  int $transactionStatus
     * @param  string $message
     * @return Transaction
     */
    public function createTransaction(
        DistributionBeneficiary $distributionBeneficiary,
        string $transactionId,
        \DateTime $dateSent,
        string $amountSent,
        int $transactionStatus,
        string $message = null
    ) {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        
        $transaction = new Transaction();
        $transaction->setDistributionBeneficiary($distributionBeneficiary);
        $transaction->setDateSent($dateSent);
        $transaction->setTransactionId($transactionId);
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus($transactionStatus);
        $transaction->setMessage($message);
        $transaction->setSentBy($user);
        
        $distributionBeneficiary->addTransaction($transaction);
        $user->addTransaction($transaction);
        
        $this->em->persist($transaction);
        $this->em->merge($distributionBeneficiary);
        $this->em->merge($user);
        $this->em->flush();
        
        return $transaction;
    }
    
    /**
     * Save transaction record in file
     * @param  Assistance $assistance
     * @param  array           $data
     * @return void
     */
    public function recordTransaction(Assistance $assistance, array $data)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $file_record = $dir_var . '/record_' . $assistance->getId() . '.csv';

        $fp = fopen($file_record, 'a');
        if (!file_get_contents($file_record)) {
            fputcsv($fp, array('FROM', 'DATE', 'URL', 'HTTP CODE', 'RESPONSE', 'ERROR', 'PARAMETERS'), ';');
        }

        fputcsv($fp, $data, ";");

        fclose($fp);
    }
}
