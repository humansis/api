<?php

namespace UserBundle\Entity;

use CommonBundle\Utils\ExportableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use FOS\UserBundle\Model\User as BaseUser;
use InvalidArgumentException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueueDuplicity;
use NewApiBundle\Entity\Role;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Symfony\Component\Validator\Constraints as Assert;
use TransactionBundle\Entity\Transaction;
use Doctrine\Common\Persistence\ObjectManagerAware;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 */
class User extends BaseUser implements ExportableInterface, ObjectManagerAware
{
    /** @var ObjectManager|null */
    private $em;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullUser"})
     */
    protected $id;

    /**
     * @var string
     * @SymfonyGroups({"FullUser", "FullVendor"})
     * @SymfonyGroups({"HouseholdChanges"})
     * @Assert\NotBlank(message="Username can't be empty")
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your username must be at least {{ limit }} characters long",
     *      maxMessage = "Your username cannot be longer than {{ limit }} characters"
     * )
     */
    protected $username;
    
    /**
     * @var string
     * @SymfonyGroups({"FullUser", "FullVendor"})
     */
    protected $password;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserCountry", mappedBy="user", cascade={"persist","remove"})
     * @SymfonyGroups({"FullUser"})
     */
    private $countries;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="user", cascade={"remove"})
     * @SymfonyGroups({"FullUser"})
     */
    private $projects;

    /**
     * @var string
     * @SymfonyGroups({"FullUser"})
     * @SymfonyGroups({"HouseholdChanges"})
     * @Assert\NotBlank(message="Email can't be empty")
     */
    protected $email;

    /**
     * @var Collection|Role[]
     * @ORM\ManyToMany(targetEntity="NewApiBundle\Entity\Role", inversedBy="users")
     */
    protected $roles;
    
    /**
     * @var Transaction
     *
     * @ORM\OneToMany(targetEntity="TransactionBundle\Entity\Transaction", mappedBy="sentBy")
     * @SymfonyGroups({"FullUser"})
     */
    private $transactions;

    /**
     * @ORM\OneToOne(targetEntity="\VoucherBundle\Entity\Vendor", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * @SymfonyGroups({"FullUser"})
     */
    private $vendor;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullUser"})
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(name="phonePrefix", type="string", nullable=true)
     * @SymfonyGroups({"FullUser"})
     */
    protected $phonePrefix;

    /**
     * @var int|null
     *
     * @ORM\Column(name="phoneNumber", type="integer", nullable=true)
     * @SymfonyGroups({"FullUser"})
     */
    protected $phoneNumber;

     /**
     * @var boolean
     * @ORM\Column(name="changePassword", type="boolean", options={"default" : 0})
     * @SymfonyGroups({"FullUser"})
     */
    protected $changePassword = false;

    /**
     * @var boolean
     * @ORM\Column(name="twoFactorAuthentication", type="boolean", options={"default" : 0})
     * @SymfonyGroups({"FullUser"})
     */
    protected $twoFactorAuthentication = false;

    /**
     * @var Import[]|Collection
     * 
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\Import", mappedBy="createdBy")
     */
    private $imports;

    /**
     * @var ImportBeneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportBeneficiary", mappedBy="createdBy")
     */
    private $importBeneficiaries;

    /**
     * @var ImportBeneficiaryDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportBeneficiaryDuplicity", mappedBy="decideBy")
     */
    private $importBeneficiaryDuplicities;

    /**
     * @var ImportFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportFile", mappedBy="user")
     */
    private $importFiles;

    /**
     * @var ImportQueueDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportQueueDuplicity", mappedBy="decideBy")
     */
    private $importQueueDuplicities;

    public function __construct()
    {
        parent::__construct();
        $this->countries = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->imports = new ArrayCollection();
        $this->importBeneficiaries = new ArrayCollection();
        $this->importBeneficiaryDuplicities = new ArrayCollection();
        $this->importFiles = new ArrayCollection();
        $this->importQueueDuplicities = new ArrayCollection();
    }

    public function injectObjectManager(ObjectManager $objectManager, ?ClassMetadata $classMetadata = null)
    {
        $this->em = $objectManager;
    }

    /**
     * @return ObjectManager
     */
    private function getObjectManager()
    {
        if (!$this->em instanceof ObjectManager) {
            throw new RuntimeException('You need to call injectObjectManager() first to use entity manager inside entity.');
        }

        return $this->em;
    }

    /**
     * Set id.
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Add country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return User
     */
    public function addCountry(\UserBundle\Entity\UserCountry $country)
    {
        $this->countries->add($country);

        return $this;
    }

    /**
     * Remove country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountry(\UserBundle\Entity\UserCountry $country)
    {
        return $this->countries->removeElement($country);
    }

    /**
     * Get countries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * Add userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return User
     */
    public function addUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        $this->projects[] = $userProject;

        return $this;
    }

    /**
     * Remove userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        return $this->projects->removeElement($userProject);
    }

    /**
     * Get projects.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }
    
    /**
     * Get the value of Transaction
     *
     * @return Transaction
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
 
    /**
     * Add a Transaction
     *
     * @param Transaction transaction
     *
     * @return self
     */
    public function addTransaction(Transaction $transaction)
    {
        $this->transactions[] = $transaction;
 
        return $this;
    }
    
    /**
     * Remove a Transaction
     * @param  Transaction $transaction
     * @return self
     */
    public function removeTransaction(Transaction $transaction)
    {
        $this->transactions->removeElement($transaction);
        return $this;
    }
    
    /**
     * Set transactions
     *
     * @param $collection
     *
     * @return self
     */
    public function setPhones(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->transactions = $collection;

        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        return [
            'email' => $this->getEmail(),
            'role' => $this->getRoles()[0],
        ];
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
    * Set vendor.
    *
    * @param \VoucherBundle\Entity\Vendor|null $vendor
    *
    * @return User
    */
    public function setVendor(\VoucherBundle\Entity\Vendor $vendor = null)
    {
        $this->vendor = $vendor;
        return $this;
    }
    /**
    * Get vendor.
    *
    * @return \VoucherBundle\Entity\Vendor|null
    */
    public function getVendor()
    {
        return $this->vendor;
    }

        /**
     * Set phonePrefix.
     *
     * @param string $phonePrefix
     *
     * @return User
     */
    public function setPhonePrefix($phonePrefix)
    {
        $this->phonePrefix = $phonePrefix;

        return $this;
    }

    /**
     * Get phonePrefix.
     *
     * @return string
     */
    public function getPhonePrefix()
    {
        return $this->phonePrefix;
    }

    /**
     * Set phoneNumber.
     *
     * @param int|null $phoneNumber
     *
     * @return User
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber.
     *
     * @return int|null
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
    * Get changePassword.
    *
    * @return boolean
    */
    public function getChangePassword()
    {
        return $this->changePassword;
    }

    /**
    * Set changePassword.
    *
    * @param boolean $changePassword
    *
    * @return User
    */
    public function setChangePassword($changePassword)
    {
        $this->changePassword = $changePassword;
        return $this;
    }

    /**
    * Get twoFactorAuthentication.
    *
    * @return boolean
    */
    public function getTwoFactorAuthentication()
    {
        return $this->twoFactorAuthentication;
    }

    /**
    * Set twoFactorAuthentication.
    *
    * @param boolean $twoFactorAuthentication
    *
    * @return User
    */
    public function setTwoFactorAuthentication($twoFactorAuthentication)
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
        }

        return $this->roles->contains($role);
    }

    /**
     * {@inheritdoc}
     */
    public function setRoles(array $roles)
    {
        $this->roles->clear();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
        }

        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
        }

        if (!$this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @SymfonyGroups({"FullUser"})
     */
    public function getRoles()
    {
        return array_map(function (Role $role) {
            return $role->getCode();
        }, $this->roles->toArray());
    }

    /**
     * @return Collection|Import[]
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @return Collection|ImportBeneficiary[]
     */
    public function getImportBeneficiaries()
    {
        return $this->importBeneficiaries;
    }

    /**
     * @return Collection|ImportBeneficiaryDuplicity[]
     */
    public function getImportBeneficiaryDuplicities()
    {
        return $this->importBeneficiaryDuplicities;
    }

    /**
     * @return Collection|ImportFile[]
     */
    public function getImportFiles()
    {
        return $this->importFiles;
    }

    /**
     * @return Collection|ImportQueueDuplicity[]
     */
    public function getImportQueueDuplicities()
    {
        return $this->importQueueDuplicities;
    }
}
