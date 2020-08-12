<?php

namespace ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use ProjectBundle\DBAL\SectorEnum;

/**
 * Sector
 *
 * @ORM\Table(name="project_sector", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="uniq_sector_project", columns={"sector", "project_id"})})
 * })
 * @ORM\Entity(repositoryClass="ProjectBundle\Repository\SectorRepository")
 */
class ProjectSector
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullSector", "FullProject"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="sector", type="enum_sector", nullable=false)
     * @Groups({"FullSector", "FullProject"})
     */
    private $sector;

    // /**
    //  * @var string|null
    //  *
    //  * @ORM\Column(name="subsector", type="enum_sector", nullable=true)
    //  * @Groups({"FullSector", "FullProject"})
    //  */
    // private $subSector;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="sectors")
     */
    private $project;

    /**
     * ProjectSector constructor.
     *
     * @param string      $sector
     * @param string|null $subSector
     * @param Project     $project
     */
    public function __construct(string $sector, Project $project, ?string $subSector)
    {
        $this->sector = $sector;
        $this->subSector = $subSector;
        $this->project = $project;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSector(): string
    {
        return $this->sector;
    }

    /**
     * @param string $sector
     */
    public function setSector(string $sector): void
    {
        $this->sector = $sector;
    }

    /**
     * backward compatibility
     *
     * @return string
     */
    public function getName(): string
    {
        return SectorEnum::getLabel($this->getSector());
    }

    // /**
    //  * @return string|null
    //  */
    // public function getSubSector(): ?string
    // {
    //     return $this->subSector;
    // }
    //
    // /**
    //  * @param string|null $subSector
    //  */
    // public function setSubSector(?string $subSector): void
    // {
    //     $this->subSector = $subSector;
    // }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project): void
    {
        $this->project = $project;
    }
}
