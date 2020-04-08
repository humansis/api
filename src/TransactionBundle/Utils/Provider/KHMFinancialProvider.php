<?php

namespace TransactionBundle\Utils\Provider;

use DistributionBundle\Entity\DistributionBeneficiary;

use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\TransactionBundle;

/**
 * Class KHMFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
class KHMFinancialProvider extends DefaultFinancialProvider
{

    /**
     * @var string
     */
    protected $url = "https://stageonline.wingmoney.com:8443/RestEngine";
    protected $url_prod = "https://hir.wingmoney.com:8443/RestServer";
    /**
     * @var string
     */
    private $token;
    /**
     * @var \DateTime
     */
    private $lastTokenDate;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var boolean
     */
    private $production;

    /**
     * Get token to connect to API
     * @param DistributionData $distributionData
     * @return object token
     * @throws \Exception
     */
    public function getToken(DistributionData $distributionData)
    {
        $organizationWINGCashTransfer = $this->em->getRepository(OrganizationServices::class)->findOneByService("WING Cash Transfer");

        if (! $organizationWINGCashTransfer->getEnabled()) {
            throw new \Exception("This service is not enabled for the organization");
        }

        $this->password = $organizationWINGCashTransfer->getParameterValue('password');
        $this->username = $organizationWINGCashTransfer->getParameterValue('username');
        $this->production = $organizationWINGCashTransfer->getParameterValue('production') ? $organizationWINGCashTransfer->getParameterValue('production') : false;

        if (!$this->password || !$this->username) {
            throw new \Exception("This service has no parameters specified");
        }

        // $this->username = $FP->getUsername();
        // $this->password = base64_decode($FP->getPassword());
        
        $route = "/oauth/token";
        $body = array(
            "username"      => $this->username,
            "password"      => $this->password,
            "grant_type"    => "password",
            "client_id"     => "third_party",
            "client_secret" => "16681c9ff419d8ecc7cfe479eb02a7a",
            "scope"         => "trust"
        );
        
        try {
            $this->token = $this->sendRequest($distributionData, "POST", $route, $body);
            $this->lastTokenDate = new \DateTime();
            return $this->token;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Send money to one beneficiary
     * @param  string                  $phoneNumber
     * @param  DistributionBeneficiary $distributionBeneficiary
     * @param  float                   $amount
     * @param  string                  $currency
     * @return Transaction
     * @throws \Exception
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        DistributionBeneficiary $distributionBeneficiary,
        float $amount,
        string $currency
    ) {
        $distributionData = $distributionBeneficiary->getDistributionData();
        $route = "/api/v1/sendmoney/nonwing/commit";
        $body = array(
            "amount"          => $amount,
            "currency"        => $currency,
            "sender_msisdn"   => "012249184",
            "receiver_msisdn" => $phoneNumber,
            "sms_to"          => "PAYEE"
        );
        
        try {
            $sent = $this->sendRequest($distributionData, "POST", $route, $body);
            if (property_exists($sent, 'error_code')) {
                $transaction = $this->createTransaction(
                    $distributionBeneficiary,
                    '',
                    new \DateTime(),
                    $currency . ' ' . $amount,
                    0,
                    $sent->message ?: ''
                );
                
                return $transaction;
            }
        } catch (Exception $e) {
            throw $e;
        }
        
        try {
            $response = $this->getStatus($distributionData, $sent->transaction_id);
        } catch (\Exception $e) {
            throw $e;
        }
        
        $transaction = $this->createTransaction(
            $distributionBeneficiary,
            $response->transaction_id,
            new \DateTime(),
            $response->amount,
            1,
            property_exists($response, 'message') ? $response->message : $sent->passcode
        );
        
        return $transaction;
    }

    /**
     * Update status of transaction (check if money has been picked up)
     * @param  Transaction $transaction
     * @return object
     * @throws \Exception
     */
    public function updateStatusTransaction(Transaction $transaction)
    {
        try {
            $response = $this->getStatus($transaction->getDistributionBeneficiary()->getDistributionData(), $transaction->getTransactionId());
        } catch (\Exception $e) {
            throw $e;
        }
        
        if (property_exists($response, 'cashout_status') && $response->cashout_status === "Complete") {
            $transaction->setMoneyReceived(true);
            $transaction->setPickupDate(new \DateTime());
            
            $this->em->merge($transaction);
            $this->em->flush();
        }
        
        return $transaction;
    }

    /**
     * Get status of transaction
     * @param DistributionData $distributionData
     * @param  string $transaction_id
     * @return object
     * @throws \Exception
     */
    public function getStatus(DistributionData $distributionData, string $transaction_id)
    {
        $route = "/api/v1/sendmoney/nonwing/txn_inquiry";
        $body = array(
            "transaction_id" => $transaction_id
        );
        
        try {
            $sent = $this->sendRequest($distributionData, "POST", $route, $body);
        } catch (Exception $e) {
            throw $e;
        }
        return $sent;
    }

    /**
     * Send request to WING API for Cambodia
     * @param DistributionData $distributionData
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @param  array $body body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(DistributionData $distributionData, string $type, string $route, array $body = array())
    {
        $curl = curl_init();

        $headers = array();
        
        // Not authentication request
        if (!preg_match('/\/oauth\/token/', $route)) {
            if (!$this->lastTokenDate ||
            (new \DateTime())->getTimestamp() - $this->lastTokenDate->getTimestamp() > $this->token->expires_in) {
                $this->getToken($distributionData);
            }
            array_push($headers, "Authorization: Bearer " . $this->token->access_token, "Content-type: application/json");
            $body = json_encode((object) $body);
        }
        // Authentication request
        else {
            $body = http_build_query($body); // Pass body as url-encoded string
        }
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8443",
          CURLOPT_URL            => ($this->production ? $this->url_prod : $this->url) . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_POSTFIELDS     => $body,
          CURLOPT_HTTPHEADER     => $headers,
          CURLOPT_FAILONERROR    => true,
          CURLINFO_HEADER_OUT    => true
        ));
        
        $info = curl_getinfo($curl);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);

        $bodyString = '';
        // Record request
        if (is_array($body)) {
            foreach ($body as $item) {
                if ($bodyString == '') {
                    $bodyString .= $item;
                } else {
                    $bodyString .= ', ' . $item;
                }
            }
        } else {
            $bodyString = $body;
        }

        $data = [$this->from, (new \DateTime())->format('d-m-Y h:i:s'), $info['url'], $info['http_code'], $response, $err, $bodyString];
        $this->recordTransaction($distributionData, $data);
    
        if ($err) {
            throw new \Exception($err);
        } else {
            $result = json_decode($response);
            return $result;
        }
    }
}
