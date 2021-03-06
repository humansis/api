<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\Product;

class ProductMapper implements MapperInterface
{
    /** @var Product */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Product && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Product) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Product::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getUnit(): ?string
    {
        return $this->object->getUnit();
    }

    public function getImage(): string
    {
        return $this->object->getImage();
    }

    public function getIso3(): string
    {
        return $this->object->getCountryISO3();
    }
}
