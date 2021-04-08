<?php

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\Commodity;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class CommodityControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetCommodities()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $commodity1 = $em->getRepository(Commodity::class)->findBy([])[0];
        $commodity2 = $em->getRepository(Commodity::class)->findBy([])[1];

        $this->request('GET', '/api/basic/assistances/commodities?filter[id][]='.$commodity1->getId().'&filter[id][]='.$commodity2->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": 2, 
            "data": [
                {
                    "id": '.$commodity1->getId().',
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*"
                },
                {
                    "id": '.$commodity2->getId().',
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetCommoditiesByAssistance()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(\DistributionBundle\Entity\Assistance::class)->findBy(['archived' => 0])[0];

        $this->request('GET', '/api/basic/assistances/'.$assistance->getId().'/commodities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": '.count($assistance->getCommodities()).', 
            "data": [
                {
                    "id": "*",
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

}
