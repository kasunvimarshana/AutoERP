<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Inventory\Enums\WarehouseStatus;
use Modules\Inventory\Events\WarehouseActivated;
use Modules\Inventory\Events\WarehouseCreated;
use Modules\Inventory\Events\WarehouseDeactivated;
use Modules\Inventory\Exceptions\InvalidWarehouseException;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Repositories\WarehouseRepository;

/**
 * Warehouse Service
 *
 * Handles business logic for warehouse management including CRUD operations,
 * activation/deactivation, and validation of warehouse operations.
 */
class WarehouseService
{
    public function __construct(
        private WarehouseRepository $warehouseRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new warehouse.
     *
     * @param  array  $data  Warehouse data
     */
    public function create(array $data): Warehouse
    {
        return TransactionHelper::execute(function () use ($data) {
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateWarehouseCode();
            }

            // Set default status
            $data['status'] = $data['status'] ?? WarehouseStatus::ACTIVE;
            $data['is_default'] = $data['is_default'] ?? false;

            // Validate unique code
            if ($this->warehouseRepository->findByCode($data['code'])) {
                throw new InvalidWarehouseException(
                    "Warehouse with code {$data['code']} already exists"
                );
            }

            // If this is set as default, unset other defaults
            if (! empty($data['is_default'])) {
                $this->unsetDefaultWarehouses($data['tenant_id'], $data['organization_id']);
            }

            // Create warehouse
            $warehouse = $this->warehouseRepository->create($data);

            // Fire event
            event(new WarehouseCreated($warehouse));

            return $warehouse;
        });
    }

    /**
     * Update warehouse.
     *
     * @param  string  $id  Warehouse ID
     * @param  array  $data  Update data
     */
    public function update(string $id, array $data): Warehouse
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            $warehouse = $this->warehouseRepository->findOrFail($id);

            // Validate code uniqueness if changed
            if (! empty($data['code']) && $data['code'] !== $warehouse->code) {
                $existing = $this->warehouseRepository->findByCode($data['code']);
                if ($existing && $existing->id !== $warehouse->id) {
                    throw new InvalidWarehouseException(
                        "Warehouse with code {$data['code']} already exists"
                    );
                }
            }

            // If this is being set as default, unset other defaults
            if (! empty($data['is_default']) && ! $warehouse->is_default) {
                $this->unsetDefaultWarehouses($warehouse->tenant_id, $warehouse->organization_id);
            }

            // Update warehouse
            return $this->warehouseRepository->updateAndReturn($warehouse->id, $data);
        });
    }

    /**
     * Activate warehouse.
     *
     * @param  string  $id  Warehouse ID
     */
    public function activate(string $id): Warehouse
    {
        $warehouse = $this->warehouseRepository->findOrFail($id);

        if ($warehouse->status === WarehouseStatus::ACTIVE) {
            throw new InvalidWarehouseException('Warehouse is already active');
        }

        $warehouse = $this->warehouseRepository->updateAndReturn($warehouse->id, [
            'status' => WarehouseStatus::ACTIVE,
        ]);

        // Fire event
        event(new WarehouseActivated($warehouse));

        return $warehouse;
    }

    /**
     * Deactivate warehouse.
     *
     * @param  string  $id  Warehouse ID
     */
    public function deactivate(string $id): Warehouse
    {
        $warehouse = $this->warehouseRepository->findOrFail($id);

        if ($warehouse->status === WarehouseStatus::INACTIVE) {
            throw new InvalidWarehouseException('Warehouse is already inactive');
        }

        // Validate warehouse can be deactivated
        $this->validateCanDeactivate($warehouse);

        $warehouse = $this->warehouseRepository->updateAndReturn($warehouse->id, [
            'status' => WarehouseStatus::INACTIVE,
        ]);

        // Fire event
        event(new WarehouseDeactivated($warehouse));

        return $warehouse;
    }

    /**
     * Set warehouse as default.
     *
     * @param  string  $id  Warehouse ID
     */
    public function setAsDefault(string $id): Warehouse
    {
        return TransactionHelper::execute(function () use ($id) {
            $warehouse = $this->warehouseRepository->findOrFail($id);

            if (! $warehouse->isActive()) {
                throw new InvalidWarehouseException('Only active warehouses can be set as default');
            }

            // Unset other defaults
            $this->unsetDefaultWarehouses($warehouse->tenant_id, $warehouse->organization_id);

            // Set as default
            return $this->warehouseRepository->updateAndReturn($warehouse->id, [
                'is_default' => true,
            ]);
        });
    }

    /**
     * Delete warehouse.
     *
     * @param  string  $id  Warehouse ID
     */
    public function delete(string $id): bool
    {
        $warehouse = $this->warehouseRepository->findOrFail($id);

        // Validate warehouse can be deleted
        $this->validateCanDelete($warehouse);

        return $this->warehouseRepository->delete($warehouse->id);
    }

    /**
     * Get paginated warehouses with filters.
     *
     * @param  string  $tenantId  Tenant ID (required for security)
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     */
    public function getPaginatedWarehouses(string $tenantId, array $filters, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $filters['tenant_id'] = $tenantId;
        
        return $this->warehouseRepository->searchWarehouses($filters, $perPage);
    }

    /**
     * Validate that warehouse can accept stock.
     *
     * @param  string  $warehouseId  Warehouse ID
     *
     * @throws InvalidWarehouseException
     */
    public function validateCanAcceptStock(string $warehouseId): void
    {
        $warehouse = $this->warehouseRepository->findOrFail($warehouseId);

        if (! $warehouse->canAcceptStock()) {
            throw new InvalidWarehouseException(
                "Warehouse {$warehouse->name} cannot accept stock in {$warehouse->status->value} status"
            );
        }
    }

    /**
     * Validate that warehouse can issue stock.
     *
     * @param  string  $warehouseId  Warehouse ID
     *
     * @throws InvalidWarehouseException
     */
    public function validateCanIssueStock(string $warehouseId): void
    {
        $warehouse = $this->warehouseRepository->findOrFail($warehouseId);

        if (! $warehouse->canIssueStock()) {
            throw new InvalidWarehouseException(
                "Warehouse {$warehouse->name} cannot issue stock in {$warehouse->status->value} status"
            );
        }
    }

    /**
     * Generate unique warehouse code.
     */
    private function generateWarehouseCode(): string
    {
        $prefix = config('inventory.warehouse.code_prefix', 'WH-');

        return $this->codeGenerator->generate(
            $prefix,
            fn ($code) => $this->warehouseRepository->findByCode($code) !== null,
            6
        );
    }

    /**
     * Unset default flag from all warehouses in organization.
     */
    private function unsetDefaultWarehouses(string $tenantId, string $organizationId): void
    {
        $this->warehouseRepository->bulkUpdate(
            [
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'is_default' => true,
            ],
            ['is_default' => false]
        );
    }

    /**
     * Validate warehouse can be deactivated.
     *
     * @throws InvalidWarehouseException
     */
    private function validateCanDeactivate(Warehouse $warehouse): void
    {
        if ($warehouse->is_default) {
            throw new InvalidWarehouseException(
                'Cannot deactivate default warehouse. Set another warehouse as default first.'
            );
        }

        // Check for active stock
        $hasStock = $warehouse->stockItems()
            ->whereRaw('CAST(quantity AS DECIMAL(10,2)) > 0')
            ->exists();

        if ($hasStock) {
            throw new InvalidWarehouseException(
                'Cannot deactivate warehouse with active stock. Transfer or adjust stock first.'
            );
        }
    }

    /**
     * Validate warehouse can be deleted.
     *
     * @throws InvalidWarehouseException
     */
    private function validateCanDelete(Warehouse $warehouse): void
    {
        if ($warehouse->is_default) {
            throw new InvalidWarehouseException('Cannot delete default warehouse');
        }

        // Check for any historical data
        if ($warehouse->stockItems()->exists()) {
            throw new InvalidWarehouseException(
                'Cannot delete warehouse with stock items. Deactivate instead.'
            );
        }

        if ($warehouse->stockMovementsFrom()->exists() || $warehouse->stockMovementsTo()->exists()) {
            throw new InvalidWarehouseException(
                'Cannot delete warehouse with stock movement history'
            );
        }
    }
}
