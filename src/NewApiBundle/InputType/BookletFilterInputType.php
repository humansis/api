<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use VoucherBundle\Entity\Booklet;

/**
 * @Assert\GroupSequence({"BookletFilterInputType", "Strict"})
 */
class BookletFilterInputType extends AbstractFilterInputType
{

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $id;

    /**
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback="bookletStatuses", strict=true)
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $statuses;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $currencies;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $distributions;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $beneficiaries;

    public static function bookletStatuses()
    {
        return array_keys(Booklet::statuses());
    }

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }

    public function hasStatuses(): bool
    {
        return $this->has('statuses');
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function hasCurrencies(): bool
    {
        return $this->has('currencies');
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function hasDistributions(): bool
    {
        return $this->has('distributions');
    }

    public function getDistributions(): array
    {
        return $this->distributions;
    }

    public function hasBeneficiaries(): bool
    {
        return $this->has('beneficiaries');
    }

    public function getBeneficiaries(): array
    {
        return $this->beneficiaries;
    }
}
