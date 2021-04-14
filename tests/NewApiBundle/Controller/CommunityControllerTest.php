<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class CommunityControllerTest extends BMSServiceTestCase
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
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testCreate()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Location|null $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        if (null === $location) {
            $this->markTestSkipped('There needs to be at least one location in system to complete this test');
        }

        $this->request('POST', '/api/basic/communities', [
            'longitude' => 'test longitude',
            'latitude' => 'test latitude',
            'contactGivenName' => 'test contactGivenName',
            'contactFamilyName' => 'test contactFamilyName',
            'projectIds' => [],
            'address' => [
                'type' => 'test type',
                'locationGroup' => 'test locationGroup',
                'number' => 'test number',
                'street' => 'test street',
                'postcode' => 'test postcode',
                'locationId' => $location->getId(),
            ],
            'nationalIdCard' => [
                'number' => '022-33-1547',
                'type' => 'Passport',
            ],
            'phone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $result['id'];
    }

    /**
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testCreate2()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Location|null $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        $this->request('POST', '/api/basic/communities', [
            'address' => [
                'type' => 'test type',
                'locationGroup' => 'test locationGroup',
                'number' => 'test number',
                'street' => 'test street',
                'postcode' => 'test postcode',
                'locationId' => $location->getId(),
            ],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testUpdate(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Location|null $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        $data = [
            'longitude' => 'test CHANGED',
            'latitude' => 'test latitude',
            'contactGivenName' => 'test contactGivenName',
            'contactFamilyName' => 'test contactFamilyName',
            'projectIds' => [$project->getId()],
            'address' => [
                'type' => 'test type',
                'locationGroup' => 'test locationGroup',
                'number' => 'test number',
                'street' => 'test street',
                'postcode' => 'test postcode',
                'locationId' => $location->getId(),
            ],
            'nationalIdCard' => [
                'number' => '022-33-1547',
                'type' => 'Passport',
            ],
            'phone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ];

        $this->request('PUT', '/api/basic/communities/'.$id, $data);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        $this->assertEquals($data['longitude'], $result['longitude']);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/communities/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/communities?sort[]=id.asc&filter[fulltext]=test');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/basic/communities/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     * @param int $id
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotexists(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/communities/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testGetCommunitiesByAssistance()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Community::class, 'comm')
                ->join('comm.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->where('comm.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with community in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/communities?sort[]=id.desc');

        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "name": "*",
                    "longitude": "*",
                    "latitude": "*",
                    "contactGivenName": "*",
                    "contactFamilyName": "*",
                    "addressId": "*",
                    "nationalId": "*",
                    "phoneId": "*",
                    "projectIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }
}
