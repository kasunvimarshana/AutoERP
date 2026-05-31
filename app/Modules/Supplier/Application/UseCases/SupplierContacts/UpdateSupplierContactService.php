<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierContacts;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class UpdateSupplierContactService implements UpdateSupplierContactServiceInterface
{
    public function __construct(
        private readonly SupplierContactRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $record = $this->repository->findById($id);
            if ($record === null || (int) $record->get('tenant_id') !== $this->tenantId()) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierContact not found.'));
            }

            unset($payload['tenant_id'], $payload['supplier_id'], $payload['created_by']);
            $payload['updated_by'] = $this->currentUser->currentUserId();
            $payload['row_version'] = ((int) $record->get('row_version', 1)) + 1;

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || $tenantId < 1) {
            throw new \RuntimeException('Current tenant context is required.');
        }

        return $tenantId;
    }
}
