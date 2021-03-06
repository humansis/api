<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Phone
 *
 * @ORM\Table(name="phone")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\PhoneRepository")
 */
class Phone
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=45)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance"})
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=45)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance"})
     */
    private $prefix;

    /**
     * @var boolean
     *
     * @ORM\Column(name="proxy", type="boolean")
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance"})
     */
    private $proxy = false;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Person", inversedBy="phones")
     */
    private $person;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return Phone
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set prefix.
     *
     * @param string $prefix
     *
     * @return Phone
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Phone
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set proxy.
     *
     * @param boolean $proxy
     *
     * @return Phone
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * Get proxy.
     *
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set beneficiary.
     *
     * @param Person|null $person
     *
     * @return Phone
     */
    public function setPerson(?Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return Person|null
     */
    public function getPerson()
    {
        return $this->person;
    }
}
