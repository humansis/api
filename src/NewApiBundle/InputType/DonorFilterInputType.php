<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class DonorFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("string")
     */
    protected $fulltext;

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext(): string
    {
        return $this->fulltext;
    }
}
