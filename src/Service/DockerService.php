<?php

namespace MarvinManager\Service;

use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\Docker;
use Docker\API\Exception\ContainerStartNotFoundException;
use Docker\API\Exception\ContainerStopNotFoundException;
use Docker\API\Exception\ContainerRestartNotFoundException;
use Docker\API\Exception\ContainerInspectNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class DockerService
{
    private Docker $docker;

    public function __construct(
        private LoggerInterface $logger,
    ) {
        $this->docker = Docker::create();
    }

    public function listContainers(bool $all = true): array
    {
        try {
            return $this->docker->containerList(['all' => $all]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to list containers', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getContainerInfo(string $dockerName): array
    {
        try {
            $response = $this->docker->containerInspect($dockerName);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ContainerInspectNotFoundException $e) {
            $this->logger->warning('Container not found', [
                'dockerName' => $dockerName,
            ]);
            throw new \RuntimeException("Container not found: {$dockerName}", 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to inspect container', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function startContainer(string $dockerName): void
    {
        try {
            $this->logger->info('Starting container', ['dockerName' => $dockerName]);
            $this->docker->containerStart($dockerName);
            $this->logger->info('Container started successfully', ['dockerName' => $dockerName]);
        } catch (ContainerStartNotFoundException $e) {
            $this->logger->error('Container not found for start', [
                'dockerName' => $dockerName,
            ]);
            throw new \RuntimeException("Container not found: {$dockerName}", 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to start container', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function stopContainer(string $dockerName, int $timeout = 10): void
    {
        try {
            $this->logger->info('Stopping container', [
                'dockerName' => $dockerName,
                'timeout' => $timeout,
            ]);
            $this->docker->containerStop($dockerName, ['t' => $timeout]);
            $this->logger->info('Container stopped successfully', ['dockerName' => $dockerName]);
        } catch (ContainerStopNotFoundException $e) {
            $this->logger->error('Container not found for stop', [
                'dockerName' => $dockerName,
            ]);
            throw new \RuntimeException("Container not found: {$dockerName}", 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to stop container', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function restartContainer(string $dockerName, int $timeout = 10): void
    {
        try {
            $this->logger->info('Restarting container', [
                'dockerName' => $dockerName,
                'timeout' => $timeout,
            ]);
            $this->docker->containerRestart($dockerName, ['t' => $timeout]);
            $this->logger->info('Container restarted successfully', ['dockerName' => $dockerName]);
        } catch (ContainerRestartNotFoundException $e) {
            $this->logger->error('Container not found for restart', [
                'dockerName' => $dockerName,
            ]);
            throw new \RuntimeException("Container not found: {$dockerName}", 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to restart container', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function execCommand(string $dockerName, string $command, array $args = []): string
    {
        try {
            $this->logger->info('Executing command in container', [
                'dockerName' => $dockerName,
                'command' => $command,
                'args' => $args,
            ]);

            // Créer l'exec avec l'API Docker
            $execConfig = new ContainersIdExecPostBody([
                'AttachStdout' => true,
                'AttachStderr' => true,
                'Cmd' => array_merge([$command], $args),
            ]);

            $execCreateResponse = $this->docker->containerExec($dockerName, $execConfig);
            $execData = json_decode($execCreateResponse->getBody()->getContents(), true);
            $execId = $execData['Id'];

            // Démarrer l'exec
            $execStartConfig = new ExecIdStartPostBody([
                'Detach' => false,
                'Tty' => false,
            ]);

            $execStartResponse = $this->docker->execStart($execId, $execStartConfig);
            $output = $execStartResponse->getBody()->getContents();

            $this->logger->info('Command executed successfully', [
                'dockerName' => $dockerName,
                'outputLength' => strlen($output),
            ]);

            return $output;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to execute command in container', [
                'dockerName' => $dockerName,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getLogs(string $dockerName, int $tail = 100): string
    {
        try {
            $this->logger->debug('Fetching container logs', [
                'dockerName' => $dockerName,
                'tail' => $tail,
            ]);

            $response = $this->docker->containerLogs($dockerName, [
                'stdout' => true,
                'stderr' => true,
                'tail' => $tail,
            ]);

            return $response->getBody()->getContents();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch container logs', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function isContainerRunning(string $dockerName): bool
    {
        try {
            $info = $this->getContainerInfo($dockerName);
            return $info['State']['Running'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getContainerStatus(string $dockerName): string
    {
        try {
            $info = $this->getContainerInfo($dockerName);
            $state = $info['State'] ?? [];

            if ($state['Running'] ?? false) {
                return 'running';
            }
            if ($state['Paused'] ?? false) {
                return 'paused';
            }
            if ($state['Restarting'] ?? false) {
                return 'restarting';
            }
            if ($state['Dead'] ?? false) {
                return 'dead';
            }

            return 'stopped';
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get container status', [
                'dockerName' => $dockerName,
                'error' => $e->getMessage(),
            ]);
            return 'unknown';
        }
    }
}
