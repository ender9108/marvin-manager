<?php

namespace MarvinManager\Service;

use EnderLab\MarvinManagerBundle\Messenger\ManagerRequestCommand;
use EnderLab\MarvinManagerBundle\Messenger\ManagerResponseCommand;
use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;
use MarvinManager\Repository\WorkerRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class WorkerService
{
    public function __construct(
        private SupervisorService $supervisorService,
        private WorkerRepository $workerRepository,
        #[Autowire(service: 'messenger.default_bus')]
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function processRequestAction(ManagerRequestCommand $request): void
    {
        match($request->action) {
            ManagerActionReference::ACTION_START->value => $this->processStartRequest($request),
            ManagerActionReference::ACTION_RESTART->value => $this->processRestartRequest($request),
            ManagerActionReference::ACTION_STOP->value => $this->processStopRequest($request),
            default => throw new \RuntimeException("Unknown action: {$request->action} for worker"),
        };
    }

    public function processStartRequest(ManagerRequestCommand $request): void
    {
        try {
            $this->logger->info('Processing start worker request', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId, // containerId sert aussi pour workerId
            ]);

            // Récupérer le worker depuis la BDD
            $worker = $this->workerRepository->findById($request->containerId);

            if (!$worker) {
                throw new \RuntimeException("Worker not found: {$request->containerId}");
            }

            // Vérifier que l'action est autorisée
            if (!in_array('start', $worker->allowedActions, true)) {
                throw new \RuntimeException("Start action not allowed for worker: {$worker->label}");
            }

            // Démarrer le worker
            $this->supervisorService->startProcess($worker->processName);

            // Vérifier que le worker est bien démarré
            sleep(2);
            $status = $this->supervisorService->getProcessStatus($worker->processName);

            if ($status !== 'running') {
                throw new \RuntimeException("Worker failed to start properly. Status: {$status}");
            }

            // Mettre à jour le statut en BDD
            $this->workerRepository->updateStatus($worker->id, $status);

            // Récupérer les infos du process pour le PID
            $processInfo = $this->supervisorService->getProcessInfo($worker->processName);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'start',
                success: true,
                output: "Worker started successfully. Status: {$status}, PID: {$processInfo['pid']}",
                metadata: ['pid' => $processInfo['pid']],
            );

            $this->logger->info('Worker started successfully', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to start worker', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
                'error' => $e->getMessage(),
            ]);

            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'start',
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    public function processRestartRequest(ManagerRequestCommand $request): void
    {
        try {
            $this->logger->info('Processing restart worker request', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
            ]);

            $worker = $this->workerRepository->findById($request->containerId);

            if (!$worker) {
                throw new \RuntimeException("Worker not found: {$request->containerId}");
            }

            if (!in_array('restart', $worker->allowedActions, true)) {
                throw new \RuntimeException("Restart action not allowed for worker: {$worker->label}");
            }

            // Redémarrer le worker
            $this->supervisorService->restartProcess($worker->processName);

            // Vérifier que le worker est bien redémarré
            sleep(2);
            $status = $this->supervisorService->getProcessStatus($worker->processName);

            if ($status !== 'running') {
                throw new \RuntimeException("Worker failed to restart properly. Status: {$status}");
            }

            // Mettre à jour le statut en BDD
            $this->workerRepository->updateStatus($worker->id, $status);

            // Récupérer les infos du process
            $processInfo = $this->supervisorService->getProcessInfo($worker->processName);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'restart',
                success: true,
                output: "Worker restarted successfully. Status: {$status}, PID: {$processInfo['pid']}",
                metadata: ['pid' => $processInfo['pid']],
            );

            $this->logger->info('Worker restarted successfully', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to restart worker', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
                'error' => $e->getMessage(),
            ]);

            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'restart',
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    public function processStopRequest(ManagerRequestCommand $request): void
    {
        try {
            $this->logger->info('Processing stop worker request', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
            ]);

            $worker = $this->workerRepository->findById($request->containerId);

            if (!$worker) {
                throw new \RuntimeException("Worker not found: {$request->containerId}");
            }

            if (!in_array('stop', $worker->allowedActions, true)) {
                throw new \RuntimeException("Stop action not allowed for worker: {$worker->label}");
            }

            // Arrêter le worker
            $this->supervisorService->stopProcess($worker->processName);

            // Vérifier que le worker est bien arrêté
            sleep(1);
            $status = $this->supervisorService->getProcessStatus($worker->processName);

            // Mettre à jour le statut en BDD
            $this->workerRepository->updateStatus($worker->id, $status);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'stop',
                success: true,
                output: "Worker stopped successfully. Status: {$status}",
            );

            $this->logger->info('Worker stopped successfully', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to stop worker', [
                'correlationId' => $request->correlationId,
                'workerId' => $request->containerId,
                'error' => $e->getMessage(),
            ]);

            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'stop',
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    private function sendResponse(
        string $correlationId,
        string $entityId,
        string $action,
        bool $success,
        ?string $output = null,
        ?string $error = null,
        array $metadata = []
    ): void {
        $response = new ManagerResponseCommand(
            correlationId: $correlationId,
            entityType: 'worker',
            entityId: $entityId,
            action: $action,
            success: $success,
            output: $output,
            error: $error,
            metadata: $metadata,
        );

        $this->messageBus->dispatch($response);
    }
}
