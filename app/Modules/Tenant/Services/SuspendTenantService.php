<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Throwable;

final class SuspendTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantRecordMapperInterface $mapper,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->tenants->findById($id);

                if ($existing === null) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
                }

                $record = $this->tenants->update($id, [
                    'status' => TenantStatus::SUSPENDED,
                    'is_active' => false,
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return Result::success($this->mapper->toValueData($record));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.suspend', 'tenant_id' => (string) $id],
            ));
        }
    }
}
