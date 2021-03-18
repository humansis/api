<?php

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class UserControllerTest extends BMSServiceTestCase
{
    /** @var string */
    private $username;

    /** @var string */
    private $email;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->username = 'test-username'.time();
        $this->email = time().'test@example.org';
    }

    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->getContainer()->get('test.client');
    }

    /**
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testInitialize()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/basic/users/initialize', [
            'username' => $this->username,
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('salt', $result);

        return $result['userId'];
    }

    /**
     * @depends testInitialize
     *
     * @param int $userId
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreate(int $userId)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Project|null $project */
        $project = $this->getContainer()->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        if (null === $project) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $this->request('POST', '/api/basic/users/'.$userId, [
            'email' => $this->email,
            'password' => 'password',
            'phonePrefix' => '+420',
            'phoneNumber' => '123456789',
            'countries' => [
                'KHM',
                'SYR',
            ],
            'language' => 'english',
            'roles' => [
                'ROLE_FIELD_OFFICER',
                'ROLE_PROJECT_OFFICER',
            ],
            'projectIds' => [
                $project->getId(),
            ],
            'changePassword' => false,
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $result;
    }

    /**
     * @depends testCreate
     *
     * @param array $result
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetSalt(array $result)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/users/salt/'.$result['username']);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('salt', $result);
    }

    /**
     * @depends testCreate
     *
     * @param array $result
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpdate(array $result)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Project|null $project */
        $project = $this->getContainer()->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        if (null === $project) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'phonePrefix' => '+420',
            'phoneNumber' => '999999999',
            'countries' => [
                'KHM',
                'SYR',
            ],
            'language' => 'english',
            'roles' => [
                'ROLE_FIELD_OFFICER',
                'ROLE_PROJECT_OFFICER',
            ],
            'projectIds' => [
                $project->getId(),
            ],
            'changePassword' => false,
        ];

        $this->request('PUT', '/api/basic/users/'.$result['id'], $data);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);

        $this->assertEquals($data['phoneNumber'], $result['phoneNumber']);

        return $result['id'];
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     *
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

        $this->request('GET', '/api/basic/users/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/users?sort[]=id.desc&filter[fulltext]=test&filter[id][]='.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
        $this->assertSame($id, $result['data'][0]['id']);
    }

    /**
     * @depends testGet
     *
     * @param int $id
     *
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

        $this->request('DELETE', '/api/basic/users/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     * @param int $id
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotexists(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/users/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
