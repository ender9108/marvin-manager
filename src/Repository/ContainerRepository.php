<?php

namespace MarvinManager\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ContainerRepository
{
    private function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     */
    public function byId(string $id): array
    {
        return $this
            ->connection
            ->createQueryBuilder()
            ->select('c.*')
            ->from('system_container', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->fetchAllAssociative()
        ;
    }
}
