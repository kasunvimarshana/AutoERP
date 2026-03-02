<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\ClosePosSessionCommand;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosSession;
use Modules\Pos\Domain\Enums\PosSessionStatus;

class ClosePosSessionHandler extends BaseHandler
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ClosePosSessionCommand $command): PosSession
    {
        return $this->transaction(function () use ($command): PosSession {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (ClosePosSessionCommand $cmd): PosSession {
                    $session = $this->repository->findById($cmd->id, $cmd->tenantId);
                    if ($session === null) {
                        throw new \DomainException("POS session with ID '{$cmd->id}' not found.");
                    }
                    if ($session->status !== PosSessionStatus::Open->value) {
                        throw new \DomainException("POS session '{$cmd->id}' is not open and cannot be closed.");
                    }

                    return $this->repository->save(new PosSession(
                        id: $session->id,
                        tenantId: $session->tenantId,
                        userId: $session->userId,
                        reference: $session->reference,
                        status: PosSessionStatus::Closed->value,
                        openedAt: $session->openedAt,
                        closedAt: now()->toIso8601String(),
                        currency: $session->currency,
                        openingFloat: $session->openingFloat,
                        closingFloat: $cmd->closingFloat,
                        totalSales: $session->totalSales,
                        totalRefunds: $session->totalRefunds,
                        notes: $cmd->notes ?? $session->notes,
                        createdAt: $session->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
