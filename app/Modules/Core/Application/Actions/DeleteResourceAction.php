<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

final class DeleteResourceAction
{
    public function __construct(
        private readonly CrudRepositoryInterface $repository,
    ) {}

    public function execute(int|string $id): bool
    {
        if ($this->repository->findById($id) === null) {
            throw new NotFoundException('Record', $id);
        }

        return $this->repository->deleteById($id);
    }
}
