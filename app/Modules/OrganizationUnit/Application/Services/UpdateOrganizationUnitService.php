<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UpdateOrganizationUnitService extends BaseService implements UpdateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $organizationUnitId = (int) $data['id'];

        return DB::transaction(function () use ($organizationUnitId, $data): OrganizationUnit {
            $organizationUnit = $this->organizationUnitRepository->find($organizationUnitId);
            if (! $organizationUnit) {
                throw new OrganizationUnitNotFoundException($organizationUnitId);
            }

            $rowVersion = isset($data['row_version']) ? (int) $data['row_version'] : 0;
            if ($rowVersion !== 0 && $rowVersion !== $organizationUnit->getRowVersion()) {
                throw new ConcurrentModificationException('OrganizationUnit', $organizationUnitId);
            }

            $organizationUnit->update(
                name: isset($data['name']) ? (string) $data['name'] : $organizationUnit->getName(),
                typeId: array_key_exists('type_id', $data) ? (isset($data['type_id']) ? (int) $data['type_id'] : null) : $organizationUnit->getTypeId(),
                parentId: array_key_exists('parent_id', $data) ? (isset($data['parent_id']) ? (int) $data['parent_id'] : null) : $organizationUnit->getParentId(),
                managerUserId: array_key_exists('manager_user_id', $data) ? (isset($data['manager_user_id']) ? (int) $data['manager_user_id'] : null) : $organizationUnit->getManagerUserId(),
                code: array_key_exists('code', $data) ? (is_string($data['code']) ? $data['code'] : null) : $organizationUnit->getCode(),
                metadata: array_key_exists('metadata', $data) ? (is_array($data['metadata']) ? $data['metadata'] : null) : $organizationUnit->getMetadata(),
                isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $organizationUnit->isActive(),
                description: array_key_exists('description', $data) ? (is_string($data['description']) ? $data['description'] : null) : $organizationUnit->getDescription(),
                imagePath: array_key_exists('image_path', $data) ? (is_string($data['image_path']) ? $data['image_path'] : null) : $organizationUnit->getImagePath(),
                defaultRevenueAccountId: array_key_exists('default_revenue_account_id', $data) ? (isset($data['default_revenue_account_id']) ? (int) $data['default_revenue_account_id'] : null) : $organizationUnit->getDefaultRevenueAccountId(),
                defaultExpenseAccountId: array_key_exists('default_expense_account_id', $data) ? (isset($data['default_expense_account_id']) ? (int) $data['default_expense_account_id'] : null) : $organizationUnit->getDefaultExpenseAccountId(),
                defaultAssetAccountId: array_key_exists('default_asset_account_id', $data) ? (isset($data['default_asset_account_id']) ? (int) $data['default_asset_account_id'] : null) : $organizationUnit->getDefaultAssetAccountId(),
                defaultLiabilityAccountId: array_key_exists('default_liability_account_id', $data) ? (isset($data['default_liability_account_id']) ? (int) $data['default_liability_account_id'] : null) : $organizationUnit->getDefaultLiabilityAccountId(),
                warehouseId: array_key_exists('warehouse_id', $data) ? (isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null) : $organizationUnit->getWarehouseId(),
            );

            $saved = $this->organizationUnitRepository->save($organizationUnit);

            // Dispatch event
            Event::dispatch(new OrganizationUnitUpdated(
                organizationUnitId: $saved->getId() ?? 0,
                tenantId: $saved->getTenantId(),
                name: $saved->getName(),
                parentId: $saved->getParentId(),
                isActive: $saved->isActive(),
            ));

            return $saved;
        });
    }
}
