<?php
declare(strict_types=1);

namespace CommonBundle\Pagination;

class Paginator implements \JsonSerializable
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var int|null
     */
    private $totalCount;

    /**
     * @var int
     */
    private $page;

    /**
     * @param iterable $data
     * @param int|null $totalCount
     * @param int      $page
     */
    public function __construct(iterable $data, $totalCount = null, $page = 1)
    {
        if ($page <= 0) {
            throw new \InvalidArgumentException('Page must be greater than zero');
        }

        $this->data = $data;
        $this->totalCount = null === $totalCount ? count($data) : $totalCount;
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'totalCount' => $this->totalCount,
            'data' => $this->data,
        ];
    }
}
