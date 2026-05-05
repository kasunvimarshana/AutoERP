<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\RepositoryInterfaces;

use Modules\ServiceCenter\Domain\Entities\ServiceTask;

interface ServiceTaskRepositoryInterface
{
    public function create(ServiceTask $task): void;

    public function findById(string $id): ?ServiceTask;

    public function getByServiceOrder(string $serviceOrderId): array;

    public function update(ServiceTask $task): void;

    public function delete(string $id): void;
}
