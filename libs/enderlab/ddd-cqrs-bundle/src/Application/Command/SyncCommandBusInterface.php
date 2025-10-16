<?php

namespace EnderLab\DddCqrsBundle\Application\Command;

interface SyncCommandBusInterface
{
    public function handle(SyncCommandInterface $message): mixed;
}
