<?php

namespace VoucherBundle\Entity;

use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \VoucherBundle\Entity\Product;
use JMS\Serializer\Annotation\Groups;

/**
 * Booklet
 *
 * @ORM\Table(name="booklet")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\BookletRepository")
 */
class Booklet
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullBooklet"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @Groups({"FullBooklet"})
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="number_vouchers", type="integer")
     * @Groups({"FullBooklet"})
     */
    private $numberVouchers;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255)
     * @Groups({"FullBooklet"})
     */
    private $currency;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Groups({"FullBooklet"})
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @ORM\ManyToMany(targetEntity="\VoucherBundle\Entity\Product", inversedBy="booklets")
     * @Groups({"FullBooklet"})
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Voucher", mappedBy="booklet", orphanRemoval=true)
     * @Groups({"FullBooklet"})
     */
    private $vouchers;

    /**
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", inversedBy="booklets")
     * @Groups({"FullBooklet"})
     */
    private $distribution_beneficiary;

    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->vouchers = new ArrayCollection();
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
     * Set code.
     *
     * @param string $code
     *
     * @return Booklet
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set numberVouchers.
     *
     * @param int $numberVouchers
     *
     * @return Booklet
     */
    public function setNumberVouchers($numberVouchers)
    {
        $this->numberVouchers = $numberVouchers;

        return $this;
    }

    /**
     * Get numberVouchers.
     *
     * @return int
     */
    public function getNumberVouchers()
    {
        return $this->numberVouchers;
    }

    /**
     * Set individualValue.
     *
     * @param int $individualValue
     *
     * @return Booklet
     */
    public function setIndividualValue($individualValue)
    {
        $this->individualValue = $individualValue;

        return $this;
    }

    /**
     * Get individualValue.
     *
     * @return int
     */
    public function getIndividualValue()
    {
        return $this->individualValue;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return Booklet
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Booklet
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return Booklet
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->product->contains($product)) {
            $this->product[] = $product;
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->product->contains($product)) {
            $this->product->removeElement($product);
        }

        return $this;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers(): Collection
    {
        return $this->vouchers;
    }

    public function addVoucher(Voucher $voucher): self
    {
        if (!$this->vouchers->contains($voucher)) {
            $this->vouchers[] = $voucher;
            $voucher->setBooklet($this);
        }

        return $this;
    }

    public function removeVoucher(Voucher $voucher): self
    {
        if ($this->vouchers->contains($voucher)) {
            $this->vouchers->removeElement($voucher);
            // set the owning side to null (unless already changed)
            if ($voucher->getBooklet() === $this) {
                $voucher->setBooklet(null);
            }
        }

        return $this;
    }
}
