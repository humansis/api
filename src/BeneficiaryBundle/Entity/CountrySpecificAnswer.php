<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * CountrySpecificAnswer
 *
 * @ORM\Table(name="country_specific_answer")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CountrySpecificAnswerRepository")
 */
class CountrySpecificAnswer
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="answer", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold"})
     */
    private $answer;

    /**
     * @var CountrySpecific
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\CountrySpecific", inversedBy="countrySpecificAnswers")
     * @SymfonyGroups({"FullHousehold"})
     */
    private $countrySpecific;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household", inversedBy="countrySpecificAnswers")
     */
    private $household;

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
     * Set answer.
     *
     * @param string $answer
     *
     * @return CountrySpecificAnswer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer.
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set countrySpecific.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecific|null $countrySpecific
     *
     * @return CountrySpecificAnswer
     */
    public function setCountrySpecific(\BeneficiaryBundle\Entity\CountrySpecific $countrySpecific = null)
    {
        $this->countrySpecific = $countrySpecific;

        return $this;
    }

    /**
     * Get countrySpecific.
     *
     * @return \BeneficiaryBundle\Entity\CountrySpecific|null
     */
    public function getCountrySpecific()
    {
        return $this->countrySpecific;
    }

    /**
     * Set household.
     *
     * @param \BeneficiaryBundle\Entity\Household|null $household
     *
     * @return CountrySpecificAnswer
     */
    public function setHousehold(\BeneficiaryBundle\Entity\Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return \BeneficiaryBundle\Entity\Household|null
     */
    public function getHousehold()
    {
        return $this->household;
    }
}
