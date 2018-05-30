<?php


namespace Tests\ProjectBundle\Controller;


use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class DonorControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;

    /** @var string $namefullname */
    private $namefullname = "TEST_DONOR_NAME_PHPUNIT";

    private $body = [
        "fullname" => "TEST_DONOR_NAME_PHPUNIT",
        "shortname" => "TEST_DONOR_NAME",
        "date_added" => "2018-04-01 11:20:13",
        "notes" => "This is a note"
    ];


    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testGetDonors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/donors');
        $donors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($donors))
        {
            $project = $donors[0];

            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('fullname', $project);
            $this->assertArrayHasKey('shortname', $project);
            $this->assertArrayHasKey('date_added', $project);
            $this->assertArrayHasKey('notes', $project);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any donor in your database.");
        }
    }

    /**
     * @throws \Exception
     */
    public function testCreateDonor()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('PUT', '/api/wsse/donor', $this->body);
        $project = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $project);
        $this->assertArrayHasKey('fullname', $project);
        $this->assertArrayHasKey('shortname', $project);
        $this->assertArrayHasKey('date_added', $project);
        $this->assertArrayHasKey('notes', $project);
        $this->assertSame($project['fullname'], $this->namefullname);
    }

    /**
     * @depends testCreateDonor
     * @throws \Exception
     */
    public function testEditDonor()
    {
        $this->em->clear();
        $donor = $this->em->getRepository(Donor::class)->findOneByFullname($this->namefullname);
        if (!$donor instanceof Donor)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['fullname'] .= '(u)';
        $crawler = $this->client->request('POST', '/api/wsse/donor/' . $donor->getId(), $this->body);
        $this->body['fullname'] = $this->namefullname;

        $donor = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();

        $this->assertArrayHasKey('id', $donor);
        $this->assertArrayHasKey('fullname', $donor);
        $this->assertArrayHasKey('shortname', $donor);
        $this->assertArrayHasKey('date_added', $donor);
        $this->assertArrayHasKey('notes', $donor);
        $this->assertSame($donor['fullname'], $this->namefullname . '(u)');

        $this->em->clear();
        $donor = $this->em->getRepository(Donor::class)->findOneByFullname($this->namefullname . "(u)");
        if ($donor instanceof Donor)
        {
            $this->em->remove($donor);
            $this->em->flush();
        }
    }
}