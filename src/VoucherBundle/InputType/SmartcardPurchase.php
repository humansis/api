<?php

namespace VoucherBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class SmartcardPurchase
{
    /**
     * @var array
     *
     * @Assert\Valid()
     * @Assert\NotBlank()
     * @Assert\All({
     *      @Assert\Collection(fields={
     *          "id" = @Assert\Type("int"),
     *          "quantity" = @Assert\Type("numeric"),
     *          "value" = @Assert\Type("numeric"),
     *          "currency" = @Assert\Type("string"),
     *      })
     * })
     */
    private $products;

    /**
     * @var int ID of vendor/seller
     *
     * @Assert\Type("int")
     * @Assert\NotBlank()
     */
    private $vendorId;

    /**
     * @var \DateTimeInterface
     *
     * @Assert\DateTime()
     * @Assert\NotBlank()
     */
    private $createdAt;

    /**
     * @return array
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param array $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    /**
     * @return int
     */
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     */
    public function setVendorId(int $vendorId): void
    {
        $this->vendorId = $vendorId;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
