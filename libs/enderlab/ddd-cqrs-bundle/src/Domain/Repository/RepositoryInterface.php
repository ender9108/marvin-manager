<?php

namespace EnderLab\DddCqrsBundle\Domain\Repository;

interface RepositoryInterface
{
    public function byId(string|int $id);
}
