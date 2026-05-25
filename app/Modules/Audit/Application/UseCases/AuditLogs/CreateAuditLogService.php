<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CreateAuditLogServiceInterface;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateAuditLogService implements CreateAuditLogServiceInterface
{
    public function __construct(private readonly AuditLogRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(AuditErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}