<?php

namespace MarvinManager\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MarvinManager\Dto\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ContainerRepository
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.default_connection')]
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function findById(string $id): ?Container
    {
        try {
            $sql = 'SELECT * FROM system_container WHERE id = :id';
            $result = $this->connection->fetchAssociative($sql, ['id' => $id]);

            if ($result === false) {
                return null;
            }

            return $this->hydrate($result);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to find container by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function findByDockerLabel(string $dockerLabel): ?Container
    {
        try {
            $sql = 'SELECT * FROM system_container WHERE docker_label_value = :dockerLabel';
            $result = $this->connection->fetchAssociative($sql, ['dockerLabel' => $dockerLabel]);

            if ($result === false) {
                return null;
            }

            return $this->hydrate($result);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to find container by docker label', [
                'dockerLabel' => $dockerLabel,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function findAll(): array
    {
        try {
            $sql = 'SELECT * FROM system_container ORDER BY label_value ASC';
            $results = $this->connection->fetchAllAssociative($sql);

            return array_map(fn($row) => $this->hydrate($row), $results);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to find all containers', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function findByType(string $type): array
    {
        try {
            $sql = 'SELECT * FROM system_container WHERE type = :type ORDER BY label_value ASC';
            $results = $this->connection->fetchAllAssociative($sql, ['type' => $type]);

            return array_map(fn($row) => $this->hydrate($row), $results);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to find containers by type', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function updateStatus(string $id, string $status): void
    {
        try {
            $sql = 'UPDATE system_container SET status = :status, last_synced_at = NOW(), updated_at = NOW() WHERE id = :id';
            $this->connection->executeStatement($sql, [
                'id' => $id,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update container status', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function upsert(Container $container): void
    {
        try {
            $sql = <<<SQL
                INSERT INTO system_container (
                    id, label_value, docker_label_value, image, type, status,
                    allowed_actions_value, ports, volumes, metadata_value,
                    last_synced_at, created_at, updated_at
                ) VALUES (
                    :id, :label, :dockerLabel, :image, :type, :status,
                    :allowedActions, :ports, :volumes, :metadata,
                    :lastSyncedAt, :createdAt, :updatedAt
                )
                ON CONFLICT (id) DO UPDATE SET
                    label_value = :label,
                    docker_label_value = :dockerLabel,
                    image = :image,
                    type = :type,
                    status = :status,
                    allowed_actions_value = :allowedActions,
                    ports = :ports,
                    volumes = :volumes,
                    metadata_value = :metadata,
                    last_synced_at = :lastSyncedAt,
                    updated_at = :updatedAt
            SQL;

            $this->connection->executeStatement($sql, [
                'id' => $container->id,
                'label' => $container->label,
                'dockerLabel' => $container->dockerLabel,
                'image' => $container->image,
                'type' => $container->type,
                'status' => $container->status,
                'allowedActions' => json_encode($container->allowedActions),
                'ports' => json_encode($container->ports),
                'volumes' => json_encode($container->volumes),
                'metadata' => json_encode($container->metadata),
                'lastSyncedAt' => $container->lastSyncedAt?->format('Y-m-d H:i:s'),
                'createdAt' => $container->createdAt?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
                'updatedAt' => $container->updatedAt?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to upsert container', [
                'containerId' => $container->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function delete(string $id): void
    {
        try {
            $sql = 'DELETE FROM system_container WHERE id = :id';
            $this->connection->executeStatement($sql, ['id' => $id]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete container', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function hydrate(array $row): Container
    {
        return new Container(
            id: $row['id'],
            label: $row['label_value'],
            dockerLabel: $row['docker_label_value'],
            image: $row['image'],
            type: $row['type'],
            status: $row['status'],
            allowedActions: json_decode($row['allowed_actions_value'] ?? '[]', true),
            ports: json_decode($row['ports'] ?? '[]', true),
            volumes: json_decode($row['volumes'] ?? '[]', true),
            metadata: json_decode($row['metadata_value'] ?? '[]', true),
            lastSyncedAt: $row['last_synced_at'] ? new DateTimeImmutable($row['last_synced_at']) : null,
            updatedAt: $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null,
            createdAt: $row['created_at'] ? new DateTimeImmutable($row['created_at']) : null,
        );
    }
}
