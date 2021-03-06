<?php

namespace BeneficiaryBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Address
 *
 * @ORM\Table(name="address")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\AddressRepository")
 */
class Address
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="number", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullInstitution", "FullBeneficiary", "FullHousehold"})
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullInstitution", "FullBeneficiary", "FullHousehold"})
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullInstitution", "FullBeneficiary", "FullHousehold"})
     */
    private $postcode;

    /**
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     * @SymfonyGroups({"FullInstitution", "FullBeneficiary", "FullHousehold", "SmallHousehold"})
     */
    private $location;

    /**
     * @param string|null   $street
     * @param string|null   $number
     * @param string|null   $postCode
     * @param Location|null $location
     *
     * @return self
     */
    public static function create(?string $street, ?string $number, ?string $postCode, ?Location $location = null): self
    {
        $address = new Address();
        $address->setNumber($number)
            ->setStreet($street)
            ->setPostcode($postCode)
            ->setLocation($location);
        return $address;
    }

    public static function createFromArray(array $addressArray, Location $location): self
    {
        $address = new Address();
        $address->setNumber($addressArray['number'])
            ->setStreet($addressArray['street'])
            ->setPostcode($addressArray['postcode'])
            ->setLocation($location);
        return $address;
    }

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
     * @param string|null $number
     *
     * @return Address
     */
    public function setNumber($number = null)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set street.
     *
     * @param string $street
     *
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street.
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set postcode.
     *
     * @param string $postcode
     *
     * @return Address
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }


     /**
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Address
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function equals(self $address): bool
    {
        if ($address->number !== $this->number) return false;
        if ($address->street !== $this->street) return false;
        if ($address->postcode !== $this->postcode) return false;
        if ($address->location !== $this->location) return false;
        return true;
    }


}
