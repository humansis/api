<?php


namespace Tests\ProjectBundle\Controller;


use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class ProjectControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;
    /** @var string $name */
    private $name = "TEST_PROJECT_NAME";

    private $body = [
        "name" => "TEST_PROJECT_NAME",
        "start_date" => "2018-02-01",
        "end_date" => "2018-03-03",
        "number_of_households" => 2,
        "value" => 5,
        "notes" => "This is a note",
        "iso3" => "FR"
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
    public function testGetProjects()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/projects');
        $projects = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($projects))
        {
            $project = $projects[0];

            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('iso3', $project);
            $this->assertArrayHasKey('name', $project);
            $this->assertArrayHasKey('notes', $project);
            $this->assertArrayHasKey('value', $project);
            $this->assertArrayHasKey('donors', $project);
            $this->assertArrayHasKey('end_date', $project);
            $this->assertArrayHasKey('start_date', $project);
            $this->assertArrayHasKey('number_of_households', $project);
            $this->assertArrayHasKey('sectors', $project);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any project in your database.");
        }
    }

    /**
     * @throws \Exception
     */
    public function testCreateProject()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('PUT', '/api/wsse/project', $this->body);
        $project = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $project);
        $this->assertArrayHasKey('iso3', $project);
        $this->assertArrayHasKey('name', $project);
        $this->assertArrayHasKey('value', $project);
        $this->assertArrayHasKey('notes', $project);
        $this->assertArrayHasKey('end_date', $project);
        $this->assertArrayHasKey('start_date', $project);
        $this->assertArrayHasKey('number_of_households', $project);
        $this->assertSame($project['name'], $this->name);
    }

    /**
     * @depends testCreateProject
     * @throws \Exception
     */
    public function testEditProject()
    {
        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneByName($this->name);
        if (!$project instanceof Project)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['name'] .= '(u)';
        $crawler = $this->client->request('POST', '/api/wsse/project/' . $project->getId(), $this->body);
        $this->body['name'] = $this->name;
        $project = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();

        $this->assertArrayHasKey('id', $project);
        $this->assertArrayHasKey('iso3', $project);
        $this->assertArrayHasKey('name', $project);
        $this->assertArrayHasKey('value', $project);
        $this->assertArrayHasKey('notes', $project);
        $this->assertArrayHasKey('end_date', $project);
        $this->assertArrayHasKey('start_date', $project);
        $this->assertArrayHasKey('number_of_households', $project);
        $this->assertSame($project['name'], $this->name . "(u)");

        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneByName($this->name . "(u)");
        if ($project instanceof Project)
        {
            $this->em->remove($project);
            $this->em->flush();
        }
    }
}