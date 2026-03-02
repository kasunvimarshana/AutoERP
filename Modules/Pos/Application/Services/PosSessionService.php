<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Services;

use Modules\Pos\Application\Commands\ClosePosSessionCommand;
use Modules\Pos\Application\Commands\DeletePosSessionCommand;
use Modules\Pos\Application\Commands\OpenPosSessionCommand;
use Modules\Pos\Application\Handlers\ClosePosSessionHandler;
use Modules\Pos\Application\Handlers\DeletePosSessionHandler;
use Modules\Pos\Application\Handlers\OpenPosSessionHandler;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosSession;

class PosSessionService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $repository,
        private readonly OpenPosSessionHandler $openHandler,
        private readonly ClosePosSessionHandler $closeHandler,
        private readonly DeletePosSessionHandler $deleteHandler,
    ) {}

    public function openSession(OpenPosSessionCommand $cmd): PosSession
    {
        return $this->openHandler->handle($cmd);
    }

    public function closeSession(ClosePosSessionCommand $cmd): PosSession
    {
        return $this->closeHandler->handle($cmd);
    }

    public function deleteSession(DeletePosSessionCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?PosSession
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }
}
