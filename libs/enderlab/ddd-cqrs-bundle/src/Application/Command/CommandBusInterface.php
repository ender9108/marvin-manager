<?php

namespace EnderLab\DddCqrsBundle\Application\Command;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): void;
}
