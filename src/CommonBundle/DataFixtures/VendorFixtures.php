<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Kernel;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Vendor;

class VendorFixtures extends Fixture implements DependentFixtureInterface
{
    const REF_VENDOR_KHM = 'vendor_fixtures_khm';
    const REF_VENDOR_SYR = 'vendor_fixtures_syr';
    const REF_VENDOR_GENERIC = 'vendor_fixtures_generic';

    const VENDOR_KHM_NAME = 'Vendor from Cambodia';
    const VENDOR_SYR_NAME = 'Vendor from Syria';

    const VENDOR_COUNT_PER_COUNTRY = 3;

    /** @var Kernel */
    private $kernel;

    /** @var array */
    private $countries = [];

    public function __construct(Kernel $kernel, array $countries)
    {
        $this->kernel = $kernel;

        $this->countries = [];
        foreach ($countries as $country) {
            $this->countries[$country['iso3']] = $country;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        srand(42);

        $vendorSyr = $this->createSyrVendor($manager);
        $vendorKhm = $this->createKhmVendor($manager);

        $manager->persist($vendorSyr);
        $manager->persist($vendorKhm);


        $this->setReference(self::REF_VENDOR_SYR, $vendorSyr);
        $this->setReference(self::REF_VENDOR_KHM, $vendorKhm);

        foreach ($this->countries as $country) {
            foreach (range(1, self::VENDOR_COUNT_PER_COUNTRY) as $index) {
                $vendor = $this->createGenericVendor($manager, $country['iso3']);
                $this->setReference(self::REF_VENDOR_GENERIC.'_'.$country['iso3'].'_'.$index, $vendor);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            LocationFixtures::class,
        ];
    }

    private function createSyrVendor(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixtures::REF_VENDOR_SYR);

        $adm1 = $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'SYR']);
        $adm2 = $manager->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

        $vendor = new Vendor();
        $vendor
            ->setName(self::VENDOR_SYR_NAME)
            ->setShop('shop')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation())
            ->setVendorNo('SYR'.sprintf('%07d', random_int(100, 10000)))
            ->setContractNo('SYRSP'.sprintf('%06d', random_int(100, 10000)))
        ;

        return $vendor;
    }

    private function createKhmVendor(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixtures::REF_VENDOR_KHM);

        $adm1 = $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'KHM']);
        $adm2 = $manager->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

        $vendor = new Vendor();
        $vendor
            ->setName(self::VENDOR_KHM_NAME)
            ->setShop('market')
            ->setAddressNumber('1')
            ->setAddressStreet('Main boulevard')
            ->setAddressPostcode('54321')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation())
            ->setVendorNo('KHM'.sprintf('%07d', random_int(100, 10000)))
            ->setContractNo('KHMSP'.sprintf('%06d', random_int(100, 10000)))
        ;

        return $vendor;
    }

    private function createGenericVendor(ObjectManager $manager, string $country): Vendor
    {
        $user = $this->makeGenericUser($manager, $country);

        $adm1 = $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => $country]);
        $adm2 = $manager->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

        $vendor = new Vendor();
        $vendor
            ->setName('Generic vendor from '.$country)
            ->setShop('generic')
            ->setAddressNumber(rand(1, 1000))
            ->setAddressStreet('Main street')
            ->setAddressPostcode(rand(10000, 99999))
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation())
            ->setVendorNo($country.sprintf('%07d', random_int(100, 10000)))
            ->setContractNo($country.'SP'.sprintf('%06d', random_int(100, 10000)))
        ;

        $manager->persist($vendor);

        return $vendor;
    }

    private function makeGenericUser(ObjectManager $manager, string $country): User
    {
        static $genericUserCount = 0;
        $userIndex = ++$genericUserCount;
        $email = "vendor$userIndex.$country@example.org";
        $instance = new User();

        $instance->injectObjectManager($manager);

        $instance->setEnabled(1)
            ->setEmail($email)
            ->setEmailCanonical($email)
            ->setUsername($email)
            ->setUsernameCanonical($email)
            ->setSalt('no salt')
            ->setRoles(['ROLE_VENDOR'])
            ->setChangePassword(0);
        $instance->setPassword('no passwd');
        $manager->persist($instance);
        return $instance;
    }
}
