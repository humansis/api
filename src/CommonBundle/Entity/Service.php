<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Service
 *
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\ServiceRepository")
 */
class Service
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullOrganization"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"FullOrganization"})
     */
    private $name;

    /**
     * @var json
     *
     * @ORM\Column(name="parameters", type="json")
     * @Groups({"FullOrganization"})
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     * @Groups({"FullOrganization"})
     */
    private $country;

    /**
     * @ORM\OneToMany(targetEntity="CommonBundle\Entity\OrganizationServices", mappedBy="service", cascade={"remove"})
     *
     * @var OrganizationServices $organizationServices
     */
    private $organizationServices;

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
     * Set name.
     *
     * @param string $name
     *
     * @return Service
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parameters.
     *
     * @param json $parameters
     *
     * @return Service
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return json
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return Service
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Add OrganizationServices.
     *
     * @param \CommonBundle\Entity\OrganizationServices $organizationServices
     *
     * @return OrganizationServices
     */
    public function addOrganizationServices(\CommonBundle\Entity\OrganizationServices $organizationServices)
    {
        if (null === $this->organizationServices) {
            $this->organizationServices = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->organizationServices[] = $organizationServices;

        return $this;
    }

    /**
     * Remove OrganizationServices.
     *
     * @param \CommonBundle\Entity\OrganizationServices $organizationServices
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrganizationServices(\CommonBundle\Entity\OrganizationServices $organizationServices)
    {
        return $this->organizationServices->removeElement($organizationServices);
    }

    /**
     * Get OrganizationServices.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationServices()
    {
        return $this->organizationServices;
    }
}
