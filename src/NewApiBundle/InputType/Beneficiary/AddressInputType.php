<?php

namespace NewApiBundle\InputType\Beneficiary;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AddressInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     */
    private $number;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $street;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     */
    private $postcode;

    /**
     * @Assert\Type("integer")
     */
    private $locationId;

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string|null $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param int|null $locationId
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }
}
