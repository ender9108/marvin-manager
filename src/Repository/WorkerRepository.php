<?php

namespace MarvinManager\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MarvinManager\Dto\Worker;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

final readonly class WorkerRepository
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.default_connection')]
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function findById(string $id): ?Worker
    {
        try {
            $sql = 'SELECT * FROM system_worker WHERE id = :id';
            $result = $this->connection->fetchAssociative($sql, ['id' => $id]);

            if ($result === false) {
                return null;
            }

            return $this->hydrate($result);
        } catch (Throwable $e) {
            $this->logger->error('Failed to find worker by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function findByProcessName(string $processName): ?Worker
    {
        try {
            $sql = 'SELECT * FROM system_worker WHERE process_name_value = :processName';
            $result = $this->connection->fetchAssociative($sql, ['processName' => $processName]);

            if ($result === false) {
                return null;
            }

            return $this->hydrate($result);
        } catch (Throwable $e) {
            $this->logger->error('Failed to find worker by process name', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function findAll(): array
    {
        try {
            $sql = 'SELECT * FROM system_worker ORDER BY label_value ASC';
            $results = $this->connection->fetchAllAssociative($sql);

            return array_map(fn($row) => $this->hydrate($row), $results);
        } catch (Throwable $e) {
            $this->logger->error('Failed to find all workers', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function findByType(string $type): array
    {
        try {
            $sql = 'SELECT * FROM system_worker WHERE type = :type ORDER BY label_value ASC';
            $results = $this->connection->fetchAllAssociative($sql, ['type' => $type]);

            return array_map(fn($row) => $this->hydrate($row), $results);
        } catch (Throwable $e) {
            $this->logger->error('Failed to find workers by type', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function updateStatus(string $id, string $status): void
    {
        try {
            $sql = 'UPDATE system_worker SET status = :status, last_synced_at = NOW(), updated_at = NOW() WHERE id = :id';
            $this->connection->executeStatement($sql, [
                'id' => $id,
                'status' => $status,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to update worker status', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function upsert(Worker $worker): void
    {
        try {
            $sql = <<<SQL
                INSERT INTO system_worker (
                    id, label_value, process_name_value, command, type, status,
                    allowed_actions_value, num_procs, priority, auto_start, auto_restart,
                    metadata_value, last_synced_at, created_at, updated_at
                ) VALUES (
                    :id, :label, :processName, :command, :type, :status,
                    :allowedActions, :numProcs, :priority, :autoStart, :autoRestart,
                    :metadata, :lastSyncedAt, :createdAt, :updatedAt
                )
                ON CONFLICT (id) DO UPDATE SET
                    label = EXCLUDED.label,
                    process_name = EXCLUDED.process_name,
                    command = EXCLUDED.command,
                    type = EXCLUDED.type,
                    status = EXCLUDED.status,
                    allowed_actions = EXCLUDED.allowed_actions,
                    num_procs = EXCLUDED.num_procs,
                    priority = EXCLUDED.priority,
                    auto_start = EXCLUDED.auto_start,
                    auto_restart = EXCLUDED.auto_restart,
                    metadata = EXCLUDED.metadata,
                    last_synced_at = EXCLUDED.last_synced_at,
                    updated_at = EXCLUDED.updated_at
            SQL;

            $this->connection->executeStatement($sql, [
                'id' => $worker->id,
                'label' => $worker->label,
                'processName' => $worker->processName,
                'command' => $worker->command,
                'type' => $worker->type,
                'status' => $worker->status,
                'allowedActions' => json_encode($worker->allowedActions),
                'numProcs' => $worker->numProcs,
                'priority' => $worker->priority,
                'autoStart' => $worker->autoStart,
                'autoRestart' => $worker->autoRestart,
                'metadata' => json_encode($worker->metadata ?? []),
                'lastSyncedAt' => $worker->lastSyncedAt?->format('Y-m-d H:i:s'),
                'createdAt' => $worker->createdAt?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
                'updatedAt' => $worker->updatedAt?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to upsert worker', [
                'workerId' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function delete(string $id): void
    {
        try {
            $sql = 'DELETE FROM system_worker WHERE id = :id';
            $this->connection->executeStatement($sql, ['id' => $id]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to delete worker', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function hydrate(array $row): Worker
    {
        return new Worker(
            id: $row['id'],
            label: $row['label_value'],
            processName: $row['process_name_value'],
            command: $row['command'],
            type: $row['type'],
            allowedActions: json_decode($row['allowed_actions_value'] ?? '[]', true),
            numProcs: (int) $row['num_procs'],
            priority: (int) $row['priority'],
            autoStart: (bool) $row['auto_start'],
            autoRestart: (bool) $row['auto_restart'],
            status: $row['status'],
            metadata: json_decode($row['metadata_value'] ?? '[]', true),
            lastSyncedAt: $row['last_synced_at'] ? new DateTimeImmutable($row['last_synced_at']) : null,
            updatedAt: $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null,
            createdAt: $row['created_at'] ? new DateTimeImmutable($row['created_at']) : null,
        );
    }
}
