<?php
declare(strict_types=1);

namespace DistributionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AssistanceSelection
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
     * @var integer
     *
     * @ORM\Column(name="threshold", type="integer", nullable=false)
     */
    private $thresold = 0;

    /**
     * @var Collection|SelectionCriteria[]
     *
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\SelectionCriteria", mappedBy="assistanceSelection")
     */
    private $selectionCriteria;

    public function __construct()
    {
        $this->selectionCriteria = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getThresold(): int
    {
        return $this->thresold;
    }

    /**
     * @param int $thresold
     */
    public function setThresold(int $thresold): void
    {
        $this->thresold = $thresold;
    }

    /**
     * @return Collection|SelectionCriteria[]
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }
}
