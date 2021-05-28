<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Enum\ImportState;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ImportUpdateStatusInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull
     * @Assert\Choice(callback="allowedStates")
     */
    private $status;

    /**
     * ImportUpdateStatusInputType constructor.
     *
     * @param string|null $status
     */
    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    public static function allowedStates(): array
    {
        return [
            ImportState::INTEGRITY_CHECKING,
            ImportState::IDENTITY_CHECKING,
            ImportState::SIMILARITY_CHECKING,
            ImportState::IMPORTING,
            ImportState::CANCELED,
        ];
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
