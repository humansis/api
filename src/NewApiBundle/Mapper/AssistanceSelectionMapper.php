<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\AssistanceSelection;
use NewApiBundle\Serializer\MapperInterface;

class AssistanceSelectionMapper implements MapperInterface
{
    /** @var AssistanceSelection */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceSelection && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof AssistanceSelection) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.AssistanceSelection::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getThresold(): int
    {
        return $this->object->getThresold();
    }
}
