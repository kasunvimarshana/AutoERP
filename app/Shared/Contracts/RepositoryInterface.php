<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

interface RepositoryInterface
{
    public function findById(int $id): ?object;

    public function save(object $entity): object;

    public function delete(int $id): void;
}
