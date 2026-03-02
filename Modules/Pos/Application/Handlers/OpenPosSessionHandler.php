<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\OpenPosSessionCommand;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosSession;
use Modules\Pos\Domain\Enums\PosSessionStatus;

class OpenPosSessionHandler extends BaseHandler
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(OpenPosSessionCommand $command): PosSession
    {
        return $this->transaction(function () use ($command): PosSession {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (OpenPosSessionCommand $cmd): PosSession {
                    $active = $this->repository->findActiveByUser($cmd->tenantId, $cmd->userId);
                    if ($active !== null) {
                        throw new \DomainException(
                            "User '{$cmd->userId}' already has an active POS session (ID: {$active->id})."
                        );
                    }

                    $reference = 'POS-SESS-'.date('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

                    return $this->repository->save(new PosSession(
                        id: null,
                        tenantId: $cmd->tenantId,
                        userId: $cmd->userId,
                        reference: $reference,
                        status: PosSessionStatus::Open->value,
                        openedAt: now()->toIso8601String(),
                        closedAt: null,
                        currency: $cmd->currency,
                        openingFloat: $cmd->openingFloat,
                        closingFloat: '0.0000',
                        totalSales: '0.0000',
                        totalRefunds: '0.0000',
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
