<?php

namespace MarvinManager\Command;

use MarvinManager\Repository\ContainerRepository;
use MarvinManager\Service\DockerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'marvin:manager:update-containers-status',
    description: 'Met à jour le statut de tous les containers Docker'
)]
#[AsPeriodicTask(frequency: '1 minute', schedule: 'default')]
final class UpdateContainersStatusCommand extends Command
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly ContainerRepository $containerRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->logger->info('Starting containers status update');
            $io->text('Mise à jour du statut des containers...');

            // Récupérer tous les containers de la BDD
            $containers = $this->containerRepository->findAll();

            if (empty($containers)) {
                $io->warning('Aucun container trouvé en base de données. Lancez d\'abord la commande de sync.');
                return Command::SUCCESS;
            }

            $updated = 0;
            $errors = 0;
            $io->progressStart(count($containers));

            foreach ($containers as $container) {
                try {
                    // Récupérer le statut actuel depuis Docker
                    $newStatus = $this->dockerService->getContainerStatus($container->dockerLabel);

                    // Mettre à jour seulement si le statut a changé
                    if ($container->status !== $newStatus) {
                        $this->containerRepository->updateStatus($container->id, $newStatus);

                        $this->logger->info('Container status updated', [
                            'container' => $container->label,
                            'old_status' => $container->status,
                            'new_status' => $newStatus,
                        ]);

                        $updated++;
                    }

                } catch (\Throwable $e) {
                    $this->logger->error('Failed to update container status', [
                        'container' => $container->label,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }

                $io->progressAdvance();
            }

            $io->progressFinish();

            // Résumé
            if ($updated > 0 || $errors > 0) {
                $io->success(sprintf(
                    '%d container(s) mis à jour, %d erreur(s)',
                    $updated,
                    $errors
                ));
            } else {
                $io->info('Tous les statuts sont déjà à jour.');
            }

            $this->logger->info('Containers status update completed', [
                'updated' => $updated,
                'errors' => $errors,
                'total' => count($containers),
            ]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to update containers status', [
                'error' => $e->getMessage(),
            ]);

            $io->error('Erreur lors de la mise à jour : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
