<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\HttpKernel\Kernel;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;

/**
 * @see VendorFixtures for check vendor username(s) is same
 */
class UserFixtures extends Fixture
{

    /** @var Kernel $kernel */
    private $kernel;
    
    /** @var UserManager $manager */
    private $manager;

    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    public function __construct(UserManager $manager, EncoderFactoryInterface $encoderFactory, Kernel $kernel)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
        $this->kernel = $kernel;
    }

    // generated by:
    // bin/console security:encode-password --no-interaction PASSWORD UserBundle\\Entity\\User
    private $data = [
        [
            'email' => 'vendor@example.org',
            'passwd' => 'O06QeWJdIK+RGkP65jnCAHtnuShmhZ8YGCAt4kqYcgZZgV2UgcqPfTD4T+/Cut8vibfiBGKJGnNgDfy5hTA0iQ==',
            'salt' => 'xZOz73DpUASslYiAUHS13Ca0289F1Vg0dDWtqxiB',
            'roles' => 'ROLE_VENDOR',
        ],
        [
            'email' => 'field.officer@example.org',
            'passwd' => 'ejsXHQZLKb+t8w4TUTC/d38dAFeo3uoB2muuMRA6ahdV8U5cAcIHh37EuOUEsMa8ZgXx0efbjIoG76DBhLRHvA==',
            'salt' => 'DHZrXXwviwTt0dUkwD/fwweGpMHN1ADw3Pj0LaxD',
            'roles' => 'ROLE_FIELD_OFFICER'
        ],
        [
            'email' => 'project.officer@example.org',
            'passwd' => 'Sikp3+vafpYEmDpt++GL6topqY3ScD5kNAY846x1RW9t7HXH6EtCMU0VP7bqzsENeZUcWtTus6kUgP14JV/TeA==',
            'salt' => 'fouJULRefDDt0fflafMw9giQxstmZ4No7K6jHu2x',
            'roles' => 'ROLE_PROJECT_OFFICER'
        ],
        [
            'email' => 'project.manager@example.org',
            'passwd' => 'TI2S81KRXUNHLL5DQGUYCvYMJmyqhR1QE8FEmKje6mETRxkrOxR5WptaSrTa4UDo9zCoCvvlxtPdipkKzES0VA==',
            'salt' => 'nu0TeRJJhkaAAYrJLCIwIktObO4xtmVtDZVGLJrj',
            'roles' => 'ROLE_PROJECT_MANAGER'
        ],
        [
            'email' => 'country.manager@example.org',
            'passwd' => 'Pj+YRYibCUOzk4EgtilEJo6wYIUElVBdfIonbovm/6ADJjjCzunXHNMSd42z+TIt/nlhipHgeUTKewx778bDfw==',
            'salt' => 'IqYj6MfmudwB7Q5nMssApAoeAWumCTcSkvL0FnBL',
            'roles' => 'ROLE_COUNTRY_MANAGER'
        ],
        [
            'email' => 'regional.manager@example.org',
            'passwd' => 'Zm030mFWASHbXmC5pnaKzPAaWb5JfsoRHiqEMdxn5q5sGcHA77Yb89RTE32n+5aTGeOLO23KAFemVaNtTosbQg==',
            'salt' => 'Rneuh6LQREX+6LjbcNqifnP59x/h46vphP9jHyNv',
            'roles' => 'ROLE_REGIONAL_MANAGER'
        ],
        [
            'email' => 'admin@example.org',
            'passwd' => 'WvbKrt5YeWcDtzWg4C8uUW9a3pmHi6SkXvnvvCisIbNQqUVtaTm8Myv/Hst1IEUDv3NtrqyUDC4BygbjQ/zePw==',
            'salt' => 'fhn91jwIbBnFAgZjQZA3mE4XUrjYzWfOoZDcjt/9',
            'roles' => 'ROLE_ADMIN'
        ],
        [
            'email' => 'enumerator@example.org',
            'passwd' => 'WcHMqN9bbZN6d45R68GaghNRSmkCk7D0h42SsnYrSySMHiV2OBIyQ/tdpKocbQhW1GEOYyhEutAgiHxY2/DPzg==',
            'salt' => '6fkDJPa0eXKNkL6Ygz62jCSluL0p0lIocJKV4lBL',
            'roles' => 'ROLE_ENUMERATOR'
        ],
        [
            'email' => 'test@example.org',
            'passwd' => 'LU4oaFBtfra56OnVPLLL5JuqRVKBcIlfk3dh1I/x3++yiYg/PylXdhcXNkbv8AUQeq0s3WETYA9d9/ItapaOBg==',
            'salt' => 'LZEDazS3/5yJWLfFLnzy9udyHS0rlbZvWg8Ropns',
            'roles' => 'ROLE_ADMIN'
        ],
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $countries = ["KHM", "SYR"];

        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";
            return;
        }

        foreach ($this->data as $index => $userData) {
            $instance = $manager->getRepository(User::class)->findOneByUsername($userData['email']);
            if ($instance instanceof User) {
                echo "User {$instance->getUsername()} already exists. Ommiting.\n";
                continue;
            }

            $instance = $this->manager->createUser();
            $instance->setEnabled(1)
                ->setEmail($userData['email'])
                ->setEmailCanonical($userData['email'])
                ->setUsername($userData['email'])
                ->setUsernameCanonical($userData['email'])
                ->setSalt($userData['salt'])
                ->setRoles([$userData['roles']])
                ->setChangePassword(0);

            foreach ($countries as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($instance)
                    ->setIso3($country)
                    ->setRights($userData['roles']);
                $instance->addCountry($userCountry);
            }

            $instance->setPassword($userData['passwd']);
            $manager->persist($instance);

            $manager->flush();
        }
    }
}
