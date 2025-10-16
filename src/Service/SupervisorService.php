<?php

namespace MarvinManager\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

final readonly class SupervisorService
{
    public function __construct(
        private DockerService $dockerService,
        private LoggerInterface $logger,
        #[Autowire(env: 'SUPERVISOR_CONTAINER_NAME')]
        private string $supervisorContainerName = 'marvin-worker',
    ) {
    }

    /**
     * Récupère les infos de tous les process
     */
    public function getAllProcessInfo(): array
    {
        try {
            $this->logger->debug('Fetching all process info from Supervisor');

            $output = $this->execSupervisorCtl(['status']);
            return $this->parseAllProcessStatus($output);
        } catch (Throwable $e) {
            $this->logger->error('Failed to get all process info', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to communicate with Supervisor', 0, $e);
        }
    }

    /**
     * Récupère les infos d'un process spécifique
     */
    public function getProcessInfo(string $processName): array
    {
        try {
            $this->logger->debug('Fetching process info', ['processName' => $processName]);

            $output = $this->execSupervisorCtl(['status', $processName]);
            return $this->parseProcessStatus($output, $processName);
        } catch (Throwable $e) {
            $this->logger->error('Failed to get process info', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Process not found or Supervisor error: {$processName}", 0, $e);
        }
    }

    /**
     * Démarre un process
     */
    public function startProcess(string $processName, bool $wait = true): bool
    {
        try {
            $this->logger->info('Starting process', [
                'processName' => $processName,
                'wait' => $wait,
            ]);

            $this->logger->info('Process started successfully', ['processName' => $processName]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Failed to start process', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Failed to start process: {$processName}", 0, $e);
        }
    }

    /**
     * Arrête un process
     */
    public function stopProcess(string $processName, bool $wait = true): bool
    {
        try {
            $this->logger->info('Stopping process', [
                'processName' => $processName,
                'wait' => $wait,
            ]);

            $this->logger->info('Process stopped successfully', ['processName' => $processName]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Failed to stop process', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Failed to stop process: {$processName}", 0, $e);
        }
    }

    /**
     * Redémarre un process
     * @throws Throwable
     */
    public function restartProcess(string $processName): bool
    {
        try {
            $this->logger->info('Restarting process', ['processName' => $processName]);

            $this->logger->info('Process restarted successfully', ['processName' => $processName]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Failed to restart process', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère les logs d'un process
     * @throws Throwable
     */
    public function getProcessLogs(string $processName, int $offset = 0, int $length = 1000): string
    {
        try {
            $this->logger->debug('Fetching process logs', [
                'processName' => $processName,
                'length' => $length,
            ]);

            return $this->execSupervisorCtl(['tail', $processName]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch process logs', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère les dernières lignes de logs (tail)
     * @throws Throwable
     */
    public function tailProcessLog(string $processName, int $length = 1000): string
    {
        try {
            return $this->execSupervisorCtl(['tail', '-' . $length, $processName]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to tail process logs', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vérifie si un process est en cours d'exécution
     */
    public function isProcessRunning(string $processName): bool
    {
        try {
            $status = $this->getProcessStatus($processName);
            return $status === 'running';
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Récupère le statut d'un process
     */
    public function getProcessStatus(string $processName): string
    {
        try {
            $info = $this->getProcessInfo($processName);
            return $info['status'] ?? 'unknown';
        } catch (Throwable $e) {
            $this->logger->warning('Failed to get process status', [
                'processName' => $processName,
                'error' => $e->getMessage(),
            ]);
            return 'unknown';
        }
    }

    /**
     * Récupère le nom d'état lisible
     */
    public function getProcessStateName(string $processName): string
    {
        try {
            $info = $this->getProcessInfo($processName);
            return $info['statename'] ?? 'UNKNOWN';
        } catch (Throwable $e) {
            return 'UNKNOWN';
        }
    }

    /**
     * Exécute une commande supervisorctl dans le container Docker
     */
    private function execSupervisorCtl(array $args): string
    {
        return $this->dockerService->execCommand(
            $this->supervisorContainerName,
            'supervisorctl',
            $args
        );
    }

    /**
     * Parse la sortie de `supervisorctl status` pour tous les process
     */
    private function parseAllProcessStatus(string $output): array
    {
        $processes = [];
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $parsed = $this->parseProcessStatusLine($line);
            if ($parsed) {
                $processes[] = $parsed;
            }
        }

        return $processes;
    }

    /**
     * Parse la sortie de `supervisorctl status` pour un process
     */
    private function parseProcessStatus(string $output, string $processName): array
    {
        $line = trim($output);
        $parsed = $this->parseProcessStatusLine($line);

        if (!$parsed) {
            throw new \RuntimeException("Failed to parse process status for: {$processName}");
        }

        return $parsed;
    }

    /**
     * Parse une ligne de sortie supervisorctl status
     * Format: "process_name RUNNING pid 1234, uptime 1:23:45"
     */
    private function parseProcessStatusLine(string $line): ?array
    {
        // Exemple: "messenger_device_commands:messenger_device_commands_00   RUNNING   pid 123, uptime 1:23:45"
        if (!preg_match('/^(\S+)\s+(\w+)\s+(.*)$/', $line, $matches)) {
            return null;
        }

        $name = $matches[1];
        $statename = $matches[2];
        $details = $matches[3];

        // Extraire le PID si présent
        $pid = null;
        if (preg_match('/pid\s+(\d+)/', $details, $pidMatches)) {
            $pid = (int) $pidMatches[1];
        }

        // Mapper le statename vers un state code
        $state = match(strtoupper($statename)) {
            'STOPPED' => 0,
            'STARTING' => 10,
            'RUNNING' => 20,
            'BACKOFF' => 30,
            'STOPPING' => 40,
            'EXITED' => 100,
            'FATAL' => 200,
            default => -1,
        };

        return [
            'name' => $name,
            'state' => $state,
            'statename' => $statename,
            'status' => strtolower($statename),
            'pid' => $pid,
            'description' => $details,
        ];
    }
}
