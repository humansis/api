<?php

namespace UserBundle\Entity;

use CommonBundle\Utils\ExportableInterface;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use TransactionBundle\Entity\Transaction;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 */
class User extends BaseUser implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullUser"})
     */
    protected $id;

    /**
     * @var string
     * @Groups({"FullUser", "FullVendor"})
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
     * @Groups({"FullUser", "FullVendor"})
     */
    protected $password;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserCountry", mappedBy="user", cascade={"persist","remove"})
     * @Groups({"FullUser"})
     */
    private $countries;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="user")
     * @Groups({"FullUser"})
     */
    private $projects;

    /**
     * @var string
     * @Groups({"FullUser"})
     * @Assert\NotBlank(message="Email can't be empty")
     */
    protected $email;

    /**
     * @var array
     * @Groups({"FullUser"})
     */
    protected $roles;
    
    /**
     * @var Transaction
     *
     * @ORM\OneToMany(targetEntity="TransactionBundle\Entity\Transaction", mappedBy="sentBy")
     * @Groups({"FullUser"})
     */
    private $transactions;

    /**
     * @ORM\OneToOne(targetEntity="\VoucherBundle\Entity\Vendor", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * @Groups({"FullUser"})
     */
    private $vendor;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     * @Groups({"FullUser"})
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(name="phonePrefix", type="string", nullable=true)
     * @Groups({"FullUser"})
     */
    protected $phonePrefix;

    /**
     * @var int
     *
     * @ORM\Column(name="phoneNumber", type="integer", nullable=true)
     * @Groups({"FullUser"})
     */
    protected $phoneNumber;

     /**
     * @var boolean
     * @ORM\Column(name="changePassword", type="boolean", options={"default" : 0})
     * @Groups({"FullUser"})
     */
    protected $changePassword;

    /**
     * @var boolean
     * @ORM\Column(name="twoFactorAuthentication", type="boolean", options={"default" : 0})
     * @Groups({"FullUser"})
     */
    protected $twoFactorAuthentication;

    public function __construct()
    {
        parent::__construct();
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
        $this->countries[] = $country;

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
            'role' => $this->getRoles()[0]
        ];
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
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
     * @param int $phoneNumber
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
     * @return int
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
}
