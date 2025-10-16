<?php

namespace MarvinManager\Messenger\Handler;

use EnderLab\MarvinManagerBundle\Messenger\ManagerRequestCommand;
use MarvinManager\Service\ContainerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ManagerRequestHandler
{
    public function __construct(
        private ContainerService $containerService,
    ) {
    }

    public function __invoke(ManagerRequestCommand $request): void {
        $response = null;

        try {
            $this->containerService->processRequestAction($request);
        } catch (\Throwable $e) {

        } finally {

        }
    }
}
