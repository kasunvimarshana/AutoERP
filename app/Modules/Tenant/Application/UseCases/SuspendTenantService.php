<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Events\TenantStatusChanged;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\Contracts\UseCases\SuspendTenantServiceInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Constants\TenantStatus;
use Throwable;

final class SuspendTenantService implements SuspendTenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantRecordMapperInterface $mapper,
    ) {
    }

    public function execute(int|string $id): Result
    {
        try {
            $existing = $this->tenants->findById($id);

            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $record = $this->tenants->update($id, [
                'status' => TenantStatus::SUSPENDED,
                'is_active' => false,
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            $this->dispatchEvent(new TenantStatusChanged($record->id(), TenantStatus::SUSPENDED, false));

            return Result::success($this->mapper->toValueData($record));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function dispatchEvent(object $event): void
    {
        try {
            event($event);
        } catch (Throwable) {
            // Ignore dispatcher failures in non-framework test contexts.
        }
    }
}
