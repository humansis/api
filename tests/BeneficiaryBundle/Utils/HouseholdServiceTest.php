<?php

namespace Tests\BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\Common\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\AddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HouseholdServiceTest extends KernelTestCase
{
    /** @var HouseholdService */
    private $householdService;

    /** @var ObjectManager */
    private $entityManager;

    /** @var ValidatorInterface */
    private $validator;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        // $this->application = new Application($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->householdService = $kernel->getContainer()->get('beneficiary.household_service');
        $this->validator = $kernel->getContainer()->get('validator');
        // $this->householdService = $kernel->getContainer()->get(HouseholdService::class);
    }

    public function testCreate()
    {
        $createData = new HouseholdCreateInputType();
        $createData->setProjectIds([1]);
        $createData->setIso3('KHM');
        $createData->setAssets(["3", 2]);
        $createData->setLongitude('12.123456');
        $createData->setLatitude('54.321');
        $createData->setNotes('Lorem ipsum');

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber(123459);
        $addressData->setPostcode(12345);
        $addressData->setStreet('Fakes st.');
        $createData->setResidenceAddress($addressData);

        $createBeneficiary = new BeneficiaryInputType();
        $createBeneficiary->setGender('F');
        $createBeneficiary->setDateOfBirth('2000-01-01');
        $createBeneficiary->setIsHead(true);
        $createBeneficiary->setLocalGivenName('testGiven');
        $createBeneficiary->setLocalFamilyName('testFamily');
        $createBeneficiary->setResidencyStatus(ResidencyStatus::RESIDENT);
        $createData->addBeneficiary($createBeneficiary);

        $phone = new PhoneInputType();
        $phone->setPrefix('+855');
        $phone->setType('Mobile');
        $phone->setProxy(true);
        $phone->setNumber('123 456 789');
        $createBeneficiary->addPhone($phone);

        $violations = $this->validator->validate($createData);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                echo "[{$violation->getPropertyPath()} = '{$violation->getInvalidValue()}'] {$violation->getMessage()}\n";
            }
            $this->fail('Testing data are invalid');
        }

        $household = $this->householdService->create($createData);
        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
        $this->assertEquals('12.123456', $household->getLongitude());
        $this->assertEquals('54.321', $household->getLatitude());
        $this->assertEquals('Lorem ipsum', $household->getNotes());
        $this->assertCount(2, $household->getAssets());
        $this->assertCount(1, $household->getProjects());
        $this->assertEquals(1, $household->getProjects()[0]->getId());
        $this->assertEquals('KHM', $household->getProjects()[0]->getIso3());
        $this->assertContains(2, $household->getAssets());
        $this->assertContains(3, $household->getAssets());

        $head = $household->getHouseholdHead();
        $this->assertEquals('testFamily', $head->getPerson()->getLocalFamilyName());
        $this->assertEquals('testGiven', $head->getPerson()->getLocalGivenName());
        $this->assertNull($head->getPerson()->getEnGivenName());
        $this->assertNull($head->getPerson()->getEnFamilyName());
        $this->assertNull($head->getPerson()->getEnParentsName());

        $phones = $head->getPerson()->getPhones();
        $this->assertCount(1, $phones, "Wrong phone count");
        $this->assertEquals('+855', $phones[0]->getPrefix());
        $this->assertEquals('123 456 789', $phones[0]->getNumber());
        $this->assertEquals('Mobile', $phones[0]->getType());
        $this->assertTrue($phones[0]->getProxy());

        return $household->getId();
    }

    /**
     * @depends testCreate
     * @param $householdId
     */
    public function testUpdate($householdId)
    {
        $updateData = new HouseholdUpdateInputType();
        $updateData->setProjectIds([1]);
        $updateData->setIso3('KHM');
        $updateData->setAssets(["1", 5]);
        $updateData->setLongitude('');
        $updateData->setLatitude('');
        $updateData->setNotes('');

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber(123459);
        $addressData->setPostcode('123 45');
        $addressData->setStreet('Fakes st.');
        $updateData->setResidenceAddress($addressData);

        $createBeneficiary = new BeneficiaryInputType();
        $createBeneficiary->setGender('F');
        $createBeneficiary->setDateOfBirth('2000-01-01');
        $createBeneficiary->setIsHead(true);
        $createBeneficiary->setLocalGivenName('test');
        $createBeneficiary->setLocalFamilyName('test');
        $createBeneficiary->setResidencyStatus(ResidencyStatus::RESIDENT);
        $updateData->addBeneficiary($createBeneficiary);

        $violations = $this->validator->validate($updateData);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                echo "[{$violation->getPropertyPath()} = '{$violation->getInvalidValue()}'] {$violation->getMessage()}\n";
            }
            $this->fail('Testing data are wrong');
        }

        $household = $this->entityManager->getRepository(Household::class)->find($householdId);

        $this->validator->validate($updateData);
        $household = $this->householdService->update($household, $updateData);

        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
    }

}