<?php

namespace MarvinManager\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class WorkerRepository
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
            ->select('w.*')
            ->from('system_worker', 'w')
            ->where('w.id = :id')
            ->setParameter('id', $id)
            ->fetchAllAssociative()
        ;
    }
}
