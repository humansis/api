<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

use UserBundle\Entity\User;

/**
 * Smartcard deposit.
 *
 * @ORM\Table(name="smartcard_deposit")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\SmartcardDepositRepository")
 */
class SmartcardDeposit
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $id;

    /**
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Smartcard", inversedBy="deposites")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $smartcard;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $depositor;

    /**
     * @var AssistanceBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\AssistanceBeneficiary", inversedBy="smartcardDeposits")
     * @ORM\JoinColumn(name="distribution_beneficiary_id", nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $assistanceBeneficiary;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="decimal", precision=10, scale=2, nullable=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $balance;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $createdAt;

    protected function __construct()
    {
    }

    public static function create(
        Smartcard $smartcard,
        User $depositor,
        AssistanceBeneficiary $assistanceBeneficiary,
        $value,
        $balance,
        DateTimeInterface $createdAt
    ) {
        $entity = new self();
        $entity->depositor = $depositor;
        $entity->assistanceBeneficiary = $assistanceBeneficiary;
        $entity->value = $value;
        $entity->balance = $balance;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;

        $smartcard->addDeposit($entity);

        return $entity;
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
     * @return Smartcard
     */
    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
    }

    /**
     * @return User
     */
    public function getDepositor(): User
    {
        return $this->depositor;
    }

    /**
     * @return AssistanceBeneficiary
     */
    public function getAssistanceBeneficiary(): AssistanceBeneficiary
    {
        return $this->assistanceBeneficiary;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
