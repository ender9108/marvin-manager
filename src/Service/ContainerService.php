<?php

namespace MarvinManager\Service;

use EnderLab\MarvinManagerBundle\Messenger\ManagerRequestCommand;
use EnderLab\MarvinManagerBundle\Messenger\ManagerResponseCommand;
use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;
use MarvinManager\Repository\ContainerRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ContainerService
{
    public function __construct(
        private DockerService $dockerService,
        private ContainerRepository $containerRepository,
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
            ManagerActionReference::ACTION_BUILD->value => $this->processBuildRequest($request),
            ManagerActionReference::ACTION_EXEC_CMD->value => $this->processExecCmdRequest($request),
            default => throw new \RuntimeException("Unknown action: {$request->action}"),
        };
    }

    public function processStartRequest(ManagerRequestCommand $request): void
    {
        try {
            $this->logger->info('Processing start container request', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

            // Récupérer le container depuis la BDD
            $container = $this->containerRepository->findById($request->containerId);

            if (!$container) {
                throw new \RuntimeException("Container not found: {$request->containerId}");
            }

            // Vérifier que l'action est autorisée
            if (!in_array('start', $container->allowedActions, true)) {
                throw new \RuntimeException("Start action not allowed for container: {$container->label}");
            }

            // Démarrer le container
            $this->dockerService->startContainer($container->dockerLabel);

            // Vérifier que le container est bien démarré
            sleep(2);
            $status = $this->dockerService->getContainerStatus($container->dockerLabel);

            if ($status !== 'running') {
                throw new \RuntimeException("Container failed to start properly. Status: {$status}");
            }

            // Mettre à jour le statut en BDD
            $this->containerRepository->updateStatus($container->id, $status);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'start',
                success: true,
                output: "Container started successfully. Status: {$status}",
            );

            $this->logger->info('Container started successfully', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to start container', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
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
            $this->logger->info('Processing restart container request', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

            $container = $this->containerRepository->findById($request->containerId);

            if (!$container) {
                throw new \RuntimeException("Container not found: {$request->containerId}");
            }

            if (!in_array('restart', $container->allowedActions, true)) {
                throw new \RuntimeException("Restart action not allowed for container: {$container->label}");
            }

            // Redémarrer le container
            $this->dockerService->restartContainer($container->dockerLabel, $request->timeout);

            // Vérifier que le container est bien redémarré
            sleep(2);
            $status = $this->dockerService->getContainerStatus($container->dockerLabel);

            if ($status !== 'running') {
                throw new \RuntimeException("Container failed to restart properly. Status: {$status}");
            }

            // Mettre à jour le statut en BDD
            $this->containerRepository->updateStatus($container->id, $status);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'restart',
                success: true,
                output: "Container restarted successfully. Status: {$status}",
            );

            $this->logger->info('Container restarted successfully', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to restart container', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
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
            $this->logger->info('Processing stop container request', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

            $container = $this->containerRepository->findById($request->containerId);

            if (!$container) {
                throw new \RuntimeException("Container not found: {$request->containerId}");
            }

            if (!in_array('stop', $container->allowedActions, true)) {
                throw new \RuntimeException("Stop action not allowed for container: {$container->label}");
            }

            // Arrêter le container
            $this->dockerService->stopContainer($container->dockerLabel, $request->timeout);

            // Vérifier que le container est bien arrêté
            sleep(1);
            $status = $this->dockerService->getContainerStatus($container->dockerLabel);

            // Mettre à jour le statut en BDD
            $this->containerRepository->updateStatus($container->id, $status);

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'stop',
                success: true,
                output: "Container stopped successfully. Status: {$status}",
            );

            $this->logger->info('Container stopped successfully', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to stop container', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
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

    public function processBuildRequest(ManagerRequestCommand $request): void
    {
        // TODO: Implémenter si nécessaire (build d'image Docker)
        $this->logger->warning('Build action not yet implemented', [
            'correlationId' => $request->correlationId,
            'containerId' => $request->containerId,
        ]);

        $this->sendResponse(
            correlationId: $request->correlationId,
            entityId: $request->containerId,
            action: 'build',
            success: false,
            error: 'Build action not yet implemented',
        );
    }

    public function processExecCmdRequest(ManagerRequestCommand $request): void
    {
        try {
            $this->logger->info('Processing exec command request', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
                'command' => $request->command,
            ]);

            $container = $this->containerRepository->findById($request->containerId);

            if (!$container) {
                throw new \RuntimeException("Container not found: {$request->containerId}");
            }

            if (!in_array('exec_cmd', $container->allowedActions, true)) {
                throw new \RuntimeException("Exec command not allowed for container: {$container->label}");
            }

            if (!$request->command) {
                throw new \RuntimeException("Command is required for exec action");
            }

            // Exécuter la commande
            $output = $this->dockerService->execCommand(
                $container->dockerLabel,
                $request->command,
                $request->args
            );

            // Envoyer la réponse de succès
            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'exec_cmd',
                success: true,
                output: $output,
            );

            $this->logger->info('Command executed successfully', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to execute command', [
                'correlationId' => $request->correlationId,
                'containerId' => $request->containerId,
                'error' => $e->getMessage(),
            ]);

            $this->sendResponse(
                correlationId: $request->correlationId,
                entityId: $request->containerId,
                action: 'exec_cmd',
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
            entityType: 'container',
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
