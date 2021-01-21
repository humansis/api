<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\GroupSequence({"BookletBatchCreateInputType", "Strict"})
 */
class BookletBatchCreateInputType implements InputTypeInterface
{
    /**
     * @Assert\Choice({"KHM", "SYR", "UKR", "ETH"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $iso3;

    /**
     * @Assert\Type("int")
     * @Assert\GreaterThan(0)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $quantityOfBooklets;

    /**
     * @Assert\Type("int")
     * @Assert\GreaterThan(0)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $quantityOfVouchers;

    /**
     * @Assert\Type("array")
     * @Assert\NotNull
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"}),
     *         @Assert\GreaterThan(0, groups={"Strict"}),
     *     },
     *     groups={"Strict"}
     * )
     * @Assert\Callback({"NewApiBundle\InputType\BookletBatchCreateInputType", "validateIndividualValues"}, groups={"Strict"}),
     */
    private $individualValues;

    /**
     * @Assert\Type("int")
     * @Assert\GreaterThan(0)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $projectId;

    /**
     * @Assert\Type("string")
     */
    private $password;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $currency;

    public function validateIndividualValues($array, ExecutionContextInterface $context, $payload)
    {
        if (count($array) > $context->getObject()->getQuantityOfVouchers()) {
            $context->buildViolation('Too many individual values')
                ->atPath('individualValues')
                ->addViolation()
            ;
        }
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return int
     */
    public function getQuantityOfBooklets()
    {
        return $this->quantityOfBooklets;
    }

    public function setQuantityOfBooklets($quantityOfBooklets)
    {
        $this->quantityOfBooklets = $quantityOfBooklets;
    }

    /**
     * @return int
     */
    public function getQuantityOfVouchers()
    {
        return $this->quantityOfVouchers;
    }

    public function setQuantityOfVouchers($quantityOfVouchers)
    {
        $this->quantityOfVouchers = $quantityOfVouchers;
    }

    /**
     * @return array|int[]
     */
    public function getIndividualValues()
    {
        return $this->individualValues;
    }

    public function setIndividualValues($individualValues)
    {
        $this->individualValues = $individualValues;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}