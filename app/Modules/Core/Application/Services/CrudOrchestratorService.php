<?php

declare(strict_types=1);

namespace Modules\Core\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Actions\CreateResourceAction;
use Modules\Core\Application\Actions\DeleteResourceAction;
use Modules\Core\Application\Actions\FindResourceAction;
use Modules\Core\Application\Actions\ListResourcesAction;
use Modules\Core\Application\Actions\UpdateResourceAction;
use Modules\Core\Application\DTOs\CreateResourceDto;
use Modules\Core\Application\DTOs\ListResourcesDto;
use Modules\Core\Application\DTOs\UpdateResourceDto;

final class CrudOrchestratorService
{
    public function __construct(
        private readonly CreateResourceAction $createAction,
        private readonly UpdateResourceAction $updateAction,
        private readonly DeleteResourceAction $deleteAction,
        private readonly FindResourceAction $findAction,
        private readonly ListResourcesAction $listAction,
    ) {}

    public function create(CreateResourceDto $dto): mixed
    {
        return DB::transaction(fn (): mixed => $this->createAction->execute($dto));
    }

    public function update(UpdateResourceDto $dto): mixed
    {
        return DB::transaction(fn (): mixed => $this->updateAction->execute($dto));
    }

    public function delete(int|string $id): bool
    {
        return DB::transaction(fn (): bool => $this->deleteAction->execute($id));
    }

    public function find(int|string $id): mixed
    {
        return $this->findAction->execute($id);
    }

    public function list(ListResourcesDto $dto): mixed
    {
        return $this->listAction->execute($dto);
    }
}
