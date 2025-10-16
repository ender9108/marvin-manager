<?php

namespace MarvinManager\Service;

use EnderLab\MarvinManagerBundle\Messenger\ManagerRequestCommand;
use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;

final readonly class ContainerService
{
    public function __construct(
        public array $errors = [],
    ) {
    }

    public function processRequestAction(ManagerRequestCommand $request): bool {
        return match($request->action) {
            ManagerActionReference::ACTION_START->value => $this->processStartRequest($request),
            ManagerActionReference::ACTION_RESTART->value => $this->processRestartRequest($request),
            ManagerActionReference::ACTION_STOP->value => $this->processStopRequest($request),
            ManagerActionReference::ACTION_BUILD->value => $this->processBuildRequest($request),
            ManagerActionReference::ACTION_EXEC_CMD->value => $this->processExecCmdRequest($request),
        };
    }

    public function processStartRequest(ManagerRequestCommand $request): bool {

    }

    public function processRestartRequest(ManagerRequestCommand $request): bool {

    }

    public function processStopRequest(ManagerRequestCommand $request): bool {

    }

    public function processBuildRequest(ManagerRequestCommand $request): bool {

    }

    public function processExecCmdRequest(ManagerRequestCommand $request): bool {

    }
}
