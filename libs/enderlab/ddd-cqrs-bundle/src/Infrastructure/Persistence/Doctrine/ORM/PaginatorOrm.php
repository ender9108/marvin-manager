<?php

namespace EnderLab\DddCqrsBundle\Infrastructure\Persistence\Doctrine\ORM;

use Doctrine\ORM\Tools\Pagination\Paginator;
use EnderLab\DddCqrsBundle\Domain\Repository\PaginatorInterface;
use Exception;
use InvalidArgumentException;

final readonly class PaginatorOrm implements PaginatorInterface
{
    private int $firstResult;
    private int $maxResults;

    /**
     * @param Paginator $paginator
     */
    public function __construct(
        private Paginator $paginator,
    ) {
        $firstResult = $paginator->getQuery()->getFirstResult();
        $maxResults = $paginator->getQuery()->getMaxResults();

        if (null === $maxResults) {
            throw new InvalidArgumentException('Missing maxResults from the query.');
        }

        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
    }

    public function getItemsPerPage(): int
    {
        return $this->maxResults;
    }

    public function getCurrentPage(): int
    {
        if (0 >= $this->maxResults) {
            return 1;
        }

        return (int) (1 + floor($this->firstResult / $this->maxResults));
    }

    public function getLastPage(): int
    {
        if (0 >= $this->maxResults) {
            return 1;
        }

        return (int) (ceil($this->getTotalItems() / $this->maxResults) ?: 1);
    }

    public function getTotalItems(): int
    {
        return count($this->paginator);
    }

    /**
     * @throws Exception
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    public function getIterator(): \Traversable
    {
        return $this->paginator->getIterator();
    }
}
