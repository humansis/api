<?php

namespace TransactionBundle\Utils\Provider;

use CommonBundle\Entity\OrganizationServices;
use DistributionBundle\Entity\AssistanceBeneficiary;

use DistributionBundle\Entity\Assistance;
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
    protected $url = "https://ir.wingmoney.com:9443/RestEngine";
    protected $url_prod = "https://api.wingmoney.com:8443/RestServer";
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
     * @param Assistance $assistance
     * @return object token
     * @throws \Exception
     */
    public function getToken(Assistance $assistance)
    {
        $organizationWINGCashTransfer = $this->em->getRepository(OrganizationServices::class)->findOneByService("WING Cash Transfer");

        if (! $organizationWINGCashTransfer->getEnabled()) {
            $this->logger->error("Missing enabled configuration for Wing money service in DB", [$assistance]);
            throw new \Exception("This service is not enabled for the organization");
        }

        $this->password = $organizationWINGCashTransfer->getParameterValue('password');
        $this->username = $organizationWINGCashTransfer->getParameterValue('username');
        $this->production = $organizationWINGCashTransfer->getParameterValue('production') ? $organizationWINGCashTransfer->getParameterValue('production') : false;

        if (!$this->password || !$this->username) {
            $this->logger->error("Missing credentials for Wing money service in DB", [$assistance]);
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
        
        $this->token = $this->sendRequest($assistance, "POST", $route, $body);
        $this->lastTokenDate = new \DateTime();
        return $this->token;
    }
    
    /**
     * Send money to one beneficiary
     * @param  string                  $phoneNumber
     * @param  AssistanceBeneficiary $assistanceBeneficiary
     * @param  float                   $amount
     * @param  string                  $currency
     * @return Transaction
     * @throws \Exception
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        AssistanceBeneficiary $assistanceBeneficiary,
        float $amount,
        string $currency
    ) {
        $assistance = $assistanceBeneficiary->getAssistance();
        $route = "/api/v1/sendmoney/nonwing/commit";
        $body = array(
            "amount"          => $amount,
            "currency"        => $currency,
            "sender_msisdn"   => "012249184",
            "receiver_msisdn" => $phoneNumber,
            "sms_to"          => "PAYEE"
        );
        
        $sent = $this->sendRequest($assistance, "POST", $route, $body);
        if (property_exists($sent, 'error_code')) {
            $transaction = $this->createTransaction(
                $assistanceBeneficiary,
                '',
                new \DateTime(),
                $currency . ' ' . $amount,
                0,
                $sent->message ?: ''
            );

            return $transaction;
        }

        $response = $this->getStatus($assistance, $sent->transaction_id);

        $transaction = $this->createTransaction(
            $assistanceBeneficiary,
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
     * @return Transaction
     * @throws \Exception
     */
    public function updateStatusTransaction(Transaction $transaction): Transaction
    {
        $response = $this->getStatus($transaction->getAssistanceBeneficiary()->getAssistance(), $transaction->getTransactionId());

        if (property_exists($response, 'cashout_status') && $response->cashout_status === "Complete") {
            $transaction->setMoneyReceived(true);
            $transaction->setPickupDate(new \DateTime());
            
            $this->em->persist($transaction);
            $this->em->flush();
        }
        
        return $transaction;
    }

    /**
     * Get status of transaction
     * @param Assistance $assistance
     * @param  string $transaction_id
     * @return object
     * @throws \Exception
     */
    public function getStatus(Assistance $assistance, string $transaction_id)
    {
        $route = "/api/v1/sendmoney/nonwing/txn_inquiry";
        $body = array(
            "transaction_id" => $transaction_id
        );

        return $this->sendRequest($assistance, "POST", $route, $body);
    }

    /**
     * Send request to WING API for Cambodia
     * @param Assistance $assistance
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @param  array $body body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(Assistance $assistance, string $type, string $route, array $body = array())
    {
        $requestID = "Request#".uniqid().": ";

        $this->logger->error($requestID."started for Assistance#".$assistance->getId()." of type $type to route $route");

        $curl = curl_init();

        if (false === $curl) {
            $this->logger->error($requestID."curl_init failed");
        } else {
            $this->logger->error($requestID."Curl initialized");
        }

        $headers = array();
        
        // Not authentication request
        if (!preg_match('/\/oauth\/token/', $route)) {
            if (!$this->lastTokenDate ||
            (new \DateTime())->getTimestamp() - $this->lastTokenDate->getTimestamp() > $this->token->expires_in) {
                $this->getToken($assistance);
            }
            array_push($headers, "Authorization: Bearer " . $this->token->access_token, "Content-type: application/json");
            $body = json_encode((object) $body);
        }
        // Authentication request
        else {
            $body = http_build_query($body); // Pass body as url-encoded string
        }

        $this->logger->error($requestID."Body built");
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => ($this->production ? "8443": "9443"),
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

        foreach ($info as $key => $value) {
            $this->logger->error($requestID."curl_getinfo $key = ".$value);
        }

        $this->logger->error($requestID."Route: ".($this->production ? $this->url_prod : $this->url) . $route . "[port".($this->production ? "8443": "9443")."]");

        $err = null;
        $response = curl_exec($curl);
        $this->logger->error($requestID."curl_exec done");
        if (false === $response) {
            $this->logger->error($requestID."error branch, response === null");
            $err = curl_error($curl);
            $this->logger->error($requestID." fails: ".$err);
        } else {
            $this->logger->error($requestID."response OK, response !== null");
        }

        $duration = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
        $this->logger->error($requestID."Request time $duration s");

        curl_close($curl);

        $this->logger->error($requestID."curl_close done");

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
        $this->recordTransaction($assistance, $data);

        $this->logger->error($requestID."record logged into var/data/record_{$assistance->getId()}.csv");
    
        if ($err) {
            $this->logger->error($requestID.__METHOD__." ended with error, throw exception");
            throw new \Exception($err);
        } else {
            $this->logger->error($requestID.__METHOD__."ended correctly");
            $result = json_decode($response);
            return $result;
        }
    }
}
