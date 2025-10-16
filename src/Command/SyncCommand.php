<?php

namespace MarvinManager\Command;

use Docker\API\Model\ContainerSummary;
use Doctrine\DBAL\Exception;
use MarvinManager\Dto\Container;
use MarvinManager\Dto\Worker;
use MarvinManager\Repository\ContainerRepository;
use MarvinManager\Repository\WorkerRepository;
use MarvinManager\Service\DockerService;
use MarvinManager\Service\SupervisorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV7;

#[AsCommand(
    name: 'marvin:manager:sync',
    description: 'Synchronise les containers Docker et workers Supervisord avec la base de données'
)]
final class SyncCommand extends Command
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly SupervisorService $supervisorService,
        private readonly ContainerRepository $containerRepository,
        private readonly WorkerRepository $workerRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('containers-only', 'c', InputOption::VALUE_NONE, 'Synchroniser uniquement les containers')
            ->addOption('workers-only', 'w', InputOption::VALUE_NONE, 'Synchroniser uniquement les workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $containersOnly = $input->getOption('containers-only');
        $workersOnly = $input->getOption('workers-only');

        // Par défaut, tout synchroniser
        $syncContainers = !$workersOnly;
        $syncWorkers = !$containersOnly;

        try {
            if ($syncContainers) {
                $io->section('Synchronisation des containers Docker');
                $this->syncContainers($io);
            }

            if ($syncWorkers) {
                $io->section('Synchronisation des workers Supervisord');
                $this->syncWorkers($io);
            }

            $io->success('Synchronisation terminée avec succès !');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error('Erreur lors de la synchronisation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    private function syncContainers(SymfonyStyle $io): void
    {
        $io->text('Récupération de la liste des containers Docker...');

        $dockerContainers = $this->dockerService->listContainers(all: true);
        $io->text(sprintf('✓ %d containers trouvés', count($dockerContainers)));

        $synced = 0;
        $io->progressStart(count($dockerContainers));

        /** @var ContainerSummary $dockerContainer */
        foreach ($dockerContainers as $dockerContainer) {
            $containerName = $dockerContainer->getNames()[0] ?? 'unknown';
            $containerName = ltrim($containerName, '/'); // Docker préfixe avec /

            $containerId = $dockerContainer->getId();
            $image = $dockerContainer->getImage();
            $status = $dockerContainer->getState();

            // Créer le DTO Container
            $container = new Container(
                id: new UuidV7(),
                label: $containerName,
                dockerLabel: $containerName,
                image: $image,
                type: $this->guessContainerType($containerName),
                status: $status,
                allowedActions: $this->getDefaultAllowedActions($containerName),
                ports: $dockerContainer->getPorts() ?? [],
                volumes: [],
                metadata: [
                    'created' => $dockerContainer->getCreated() ?? null,
                    'labels' => $dockerContainer->getLabels() ?? [],
                ],
                lastSyncedAt: new \DateTimeImmutable(),
            );

            $this->containerRepository->upsert($container);
            $synced++;
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(sprintf('%d containers synchronisés', $synced));
    }

    private function syncWorkers(SymfonyStyle $io): void
    {
        $io->text('Récupération de la liste des workers Supervisord...');

        $supervisorProcesses = $this->supervisorService->getAllProcessInfo();
        $io->text(sprintf('✓ %d workers trouvés', count($supervisorProcesses)));

        $synced = 0;
        $io->progressStart(count($supervisorProcesses));

        foreach ($supervisorProcesses as $process) {
            $processName = $process['name'];
            $workerId = md5($processName); // Générer un ID stable

            // Créer le DTO Worker
            $worker = new Worker(
                id: $workerId,
                label: $processName,
                processName: $processName,
                command: $process['description'] ?? '',
                type: $this->guessWorkerType($processName),
                allowedActions: ['start', 'stop', 'restart'],
                status: $process['status'] ?? 'unknown',
                metadata: [
                    'pid' => $process['pid'] ?? null,
                    'state' => $process['state'] ?? null,
                    'statename' => $process['statename'] ?? null,
                ],
                lastSyncedAt: new \DateTimeImmutable(),
            );

            $this->workerRepository->upsert($worker);
            $synced++;
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(sprintf('%d workers synchronisés', $synced));
    }

    private function guessContainerType(string $containerName): string
    {
        return match (true) {
            str_contains($containerName, 'zigbee') => 'protocol',
            str_contains($containerName, 'mqtt') => 'protocol',
            str_contains($containerName, 'zwave') => 'protocol',
            str_contains($containerName, 'database') => 'database',
            str_contains($containerName, 'postgres') => 'database',
            str_contains($containerName, 'rabbitmq') => 'broker',
            str_contains($containerName, 'redis') => 'cache',
            str_contains($containerName, 'worker') => 'worker',
            str_contains($containerName, 'php') => 'application',
            default => 'other',
        };
    }

    private function getDefaultAllowedActions(string $containerName): array
    {
        // Les containers critiques ont moins d'actions autorisées
        if (str_contains($containerName, 'database') || str_contains($containerName, 'rabbitmq')) {
            return ['restart']; // Pas de start/stop pour éviter les accidents
        }

        return ['start', 'stop', 'restart'];
    }

    private function guessWorkerType(string $processName): string
    {
        return match (true) {
            str_contains($processName, 'messenger') => 'consumer',
            str_contains($processName, 'consumer') => 'consumer',
            str_contains($processName, 'listener') => 'listener',
            str_contains($processName, 'protocol') => 'listener',
            str_contains($processName, 'cron') => 'cron',
            str_contains($processName, 'monitor') => 'monitor',
            default => 'worker',
        };
    }
}
