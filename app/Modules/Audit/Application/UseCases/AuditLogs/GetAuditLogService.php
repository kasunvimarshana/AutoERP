<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\GetAuditLogServiceInterface;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetAuditLogService implements GetAuditLogServiceInterface
{
    public function __construct(private readonly AuditLogRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(AuditErrorCode::NOT_FOUND, 'AuditLog not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(AuditErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}