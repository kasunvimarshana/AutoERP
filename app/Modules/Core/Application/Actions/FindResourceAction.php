<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

final class FindResourceAction
{
    public function __construct(
        private readonly CrudRepositoryInterface $repository,
    ) {}

    public function execute(int|string $id): mixed
    {
        $record = $this->repository->findById($id);
        if ($record === null) {
            throw new NotFoundException('Record', $id);
        }

        return $record;
    }
}
