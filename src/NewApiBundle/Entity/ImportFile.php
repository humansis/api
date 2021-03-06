<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

/**
 * @ORM\Entity()
 */
class ImportFile
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
     * @var string
     *
     * @ORM\Column(name="filename", type="string", nullable=false)
     */
    private $filename;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_loaded", type="boolean")
     */
    private $isLoaded;

    /**
     * @var string|null
     *
     * @ORM\Column(name="saved_as_filename", type="string", nullable=true)
     */
    private $savedAsFilename;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import", inversedBy="importFiles")
     */
    private $import;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="importFiles")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=true)
     */
    private $createdAt;

    /**
     * @var ImportQueue[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportQueue", mappedBy="file", cascade={"remove"})
     */
    private $importQueues;

    public function __construct(string $filename, Import $import, User $user)
    {
        $this->filename = $filename;
        $this->import = $import;
        $this->user = $user;
        $this->createdAt = new \DateTime('now');
        $this->isLoaded = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function __toString()
    {
        return "ImportFile#{$this->getId()} ({$this->getFilename()})";
    }

    /**
     * @return Collection|ImportQueue[]
     */
    public function getImportQueues()
    {
        return $this->importQueues;
    }

    /**
     * @param Collection|ImportQueue[] $importQueues
     */
    public function setImportQueues($importQueues): void
    {
        $this->importQueues = $importQueues;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    /**
     * @param bool $isLoaded
     */
    public function setIsLoaded(bool $isLoaded): void
    {
        $this->isLoaded = $isLoaded;
    }

    /**
     * @return string|null
     */
    public function getSavedAsFilename(): ?string
    {
        return $this->savedAsFilename;
    }

    /**
     * @param string|null $savedAsFilename
     */
    public function setSavedAsFilename(?string $savedAsFilename): void
    {
        $this->savedAsFilename = $savedAsFilename;
    }
}
