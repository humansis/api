<?php

namespace Tests\UserBundle\Controller;

use Tests\BMSServiceTestCase;

class TransactionController extends BMSServiceTestCase
{
    /** @var string */
    private $username = 'TESTER_PHPUNIT@gmail.com';

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    public function testListOfPurchases()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/transactions/purchases/beneficiary/'. 213);

        $criteria = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($criteria);

        foreach ($criteria as $criterion) {
            $this->assertArrayHasKey('beneficiary', $criterion);
            $this->assertArrayHasKey('productId', $criterion);
            $this->assertArrayHasKey('productName', $criterion);
            $this->assertArrayHasKey('value', $criterion);
            $this->assertArrayHasKey('quantity', $criterion);
            $this->assertArrayHasKey('source', $criterion);
            $this->assertArrayHasKey('usedAt', $criterion);
        }
    }

    public function testListOfHouseholdPurchases()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/transactions/purchases/household/'. 5);

        $criteria = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($criteria);

        foreach ($criteria as $criterion) {
            $this->assertArrayHasKey('beneficiary', $criterion);
            $this->assertArrayHasKey('productId', $criterion);
            $this->assertArrayHasKey('productName', $criterion);
            $this->assertArrayHasKey('value', $criterion);
            $this->assertArrayHasKey('quantity', $criterion);
            $this->assertArrayHasKey('source', $criterion);
            $this->assertArrayHasKey('usedAt', $criterion);
        }
    }

    public function testSetTransactionDistributed()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/wsse/transactions/distributed', [
            'ids' => [],
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
    }

    /**
     * @depends testSetTransactionDistributed
     */
    public function testSetTransactionPickedUp()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/wsse/transactions/picked-up', [
            'ids' => [],
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
    }

    //Transactions tests are in the DistributionBundle because we needed a distribution to test the differents routes
}
