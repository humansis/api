<?php

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\GeneralReliefItem;
use Exception;
use Tests\BMSServiceTestCase;

class GeneralReliefItemControllerTest extends BMSServiceTestCase
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

    public function testGet()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy([])[0];

        $this->request('GET', '/api/basic/general-relief-items/'.$item->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonStringEqualsJsonString('{
            "id": '.$item->getId().',
            "distributed": '.(null === $item->getDistributedAt() ? 'false' : 'true').',
            "dateOfDistribution": '.(null === $item->getDistributedAt() ? 'null' : '"'.$item->getDistributedAt()->format(\DateTime::ISO8601).'"').',
            "note": '.(null === $item->getNotes() ? 'null' : '"'.$item->getNotes().'"').'
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        $this->request('GET', '/api/basic/general-relief-items?&filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testPatch()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy(['distributedAt' => null])[0];

        $this->request('PATCH', '/api/basic/general-relief-items/'.$item->getId(), [
            'distributed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": '.$item->getId().',
            "distributed": true
        }', $this->client->getResponse()->getContent());
    }
}
