<?php

namespace MarvinManager\Command;

use MarvinManager\Repository\WorkerRepository;
use MarvinManager\Service\SupervisorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'marvin:manager:update-workers-status',
    description: 'Met à jour le statut de tous les workers Supervisord'
)]
#[AsPeriodicTask(frequency: '1 minute', schedule: 'default')]
final class UpdateWorkersStatusCommand extends Command
{
    public function __construct(
        private readonly SupervisorService $supervisorService,
        private readonly WorkerRepository $workerRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->logger->info('Starting workers status update');
            $io->text('Mise à jour du statut des workers...');

            // Récupérer tous les workers de la BDD
            $workers = $this->workerRepository->findAll();

            if (empty($workers)) {
                $io->warning('Aucun worker trouvé en base de données. Lancez d\'abord la commande de sync.');
                return Command::SUCCESS;
            }

            $updated = 0;
            $errors = 0;
            $io->progressStart(count($workers));

            foreach ($workers as $worker) {
                try {
                    // Récupérer le statut actuel depuis Supervisord
                    $newStatus = $this->supervisorService->getProcessStatus($worker->processName);

                    // Mettre à jour seulement si le statut a changé
                    if ($worker->status !== $newStatus) {
                        $this->workerRepository->updateStatus($worker->id, $newStatus);

                        $this->logger->info('Worker status updated', [
                            'worker' => $worker->label,
                            'old_status' => $worker->status,
                            'new_status' => $newStatus,
                        ]);

                        $updated++;
                    }

                } catch (\Throwable $e) {
                    $this->logger->error('Failed to update worker status', [
                        'worker' => $worker->label,
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
                    '%d worker(s) mis à jour, %d erreur(s)',
                    $updated,
                    $errors
                ));
            } else {
                $io->info('Tous les statuts sont déjà à jour.');
            }

            $this->logger->info('Workers status update completed', [
                'updated' => $updated,
                'errors' => $errors,
                'total' => count($workers),
            ]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to update workers status', [
                'error' => $e->getMessage(),
            ]);

            $io->error('Erreur lors de la mise à jour : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
