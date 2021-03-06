<?php
declare(strict_types=1);

namespace NewApiBundle\Request\OrderInputType;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractSortInputType implements SortInputTypeInterface
{
    private $sort = [];

    /**
     * @return array list of accepted sort names
     */
    abstract protected function getValidNames(): array;

    public function setOrderBy($sorts)
    {
        if (!is_array($sorts)) {
            throw new BadRequestHttpException('Invalid sort definition. Array is required');
        }

        $validNames = $this->getValidNames();

        foreach ($sorts as $value) {
            if (false === strpos($value, '.')) {
                $name = $value;
                $direction = 'asc';
            } else {
                list($name, $direction) = explode('.', $value);
            }

            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                throw new BadRequestHttpException('Invalid sort direction for '.$value);
            }

            if (!in_array($name, $validNames)) {
                throw new BadRequestHttpException('Invalid sort name for '.$value);
            }

            $this->sort[$name] = $direction;
        }
    }

    public function toArray(): array
    {
        return $this->sort;
    }

    public function has(string $name): bool
    {
        return isset($this->sort[$name]);
    }
}
