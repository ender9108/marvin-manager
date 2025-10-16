<?php

namespace EnderLab\DddCqrsBundle\Application\Query;

interface QueryBusInterface
{
    public function handle(QueryInterface $message): mixed;
}
