<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\CustomerStatusHistory;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockStateChange;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\SupplierStatusHistory;
use Modules\Tax\Models\TaxTransaction;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\VehicleStatusHistory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\VehicleFinanceStatusHistory;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceStatusHistory;
use Tests\TestCase;

final class LedgerAndHistoryImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_status_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new CustomerStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'customer_id' => 1,
                'from_status' => CustomerStatus::PendingApproval,
                'to_status' => CustomerStatus::Active,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('CustomerStatusHistory is an immutable ledger and cannot be updated.');
            $history->to_status = CustomerStatus::Inactive;
            $history->save();
        });
    }

    public function test_customer_status_history_cannot_be_deleted(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new CustomerStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'customer_id' => 1,
                'from_status' => CustomerStatus::PendingApproval,
                'to_status' => CustomerStatus::Active,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('CustomerStatusHistory is an immutable ledger and cannot be deleted.');
            $history->delete();
        });
    }

    public function test_supplier_status_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new SupplierStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'supplier_id' => 1,
                'from_status' => SupplierStatus::PendingApproval,
                'to_status' => SupplierStatus::Active,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('SupplierStatusHistory is an immutable ledger and cannot be updated.');
            $history->to_status = SupplierStatus::Inactive;
            $history->save();
        });
    }

    public function test_vehicle_status_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new VehicleStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'vehicle_id' => 1,
                'from_status' => VehicleStatus::Inactive,
                'to_status' => VehicleStatus::Active,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('VehicleStatusHistory is an immutable ledger and cannot be updated.');
            $history->to_status = VehicleStatus::Inactive;
            $history->save();
        });
    }

    public function test_vehicle_service_status_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new VehicleServiceStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'job_id' => 1,
                'from_status' => VehicleServiceJobStatus::Draft,
                'to_status' => VehicleServiceJobStatus::InProgress,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('VehicleServiceStatusHistory is an immutable ledger and cannot be updated.');
            $history->to_status = VehicleServiceJobStatus::Completed;
            $history->save();
        });
    }

    public function test_agreement_adjustment_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new AgreementAdjustmentHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'agreement_adjustment_id' => 1,
                'from_status' => AgreementAdjustmentStatus::Draft,
                'to_status' => AgreementAdjustmentStatus::Applied,
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('AgreementAdjustmentHistory is an immutable ledger and cannot be updated.');
            $history->to_status = AgreementAdjustmentStatus::Voided;
            $history->save();
        });
    }

    public function test_vehicle_finance_status_history_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $history = new VehicleFinanceStatusHistory;
            $history->forceFill([
                'tenant_id' => 1,
                'vehicle_id' => 1,
                'from_status' => 'draft',
                'to_status' => 'active',
            ]);
            $history->syncOriginal();
            $history->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('VehicleFinanceStatusHistory is an immutable ledger and cannot be updated.');
            $history->to_status = 'archived';
            $history->save();
        });
    }

    public function test_tax_transaction_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $tx = new TaxTransaction;
            $tx->forceFill([
                'tenant_id' => 1,
                'source_type' => 'invoice',
                'source_id' => 1,
            ]);
            $tx->syncOriginal();
            $tx->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('TaxTransaction is an immutable ledger and cannot be updated.');
            $tx->source_id = 2;
            $tx->save();
        });
    }

    public function test_inventory_stock_state_change_is_immutable(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $change = new InventoryStockStateChange;
            $change->forceFill([
                'tenant_id' => 1,
                'movement_id' => 1,
                'from_state' => InventoryStockState::Available,
                'to_state' => InventoryStockState::Reserved,
            ]);
            $change->syncOriginal();
            $change->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('InventoryStockStateChange is an immutable ledger and cannot be updated.');
            $change->to_state = InventoryStockState::Scrapped;
            $change->save();
        });
    }

    public function test_inventory_movement_cannot_be_deleted(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $movement = new InventoryMovement;
            $movement->forceFill([
                'tenant_id' => 1,
                'status' => InventoryStatus::Posted,
            ]);
            $movement->syncOriginal();
            $movement->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('InventoryMovement is an immutable ledger and cannot be deleted.');
            $movement->delete();
        });
    }

    public function test_posted_inventory_movement_cannot_have_core_fields_tampered(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $movement = new InventoryMovement;
            $movement->setRawAttributes([
                'tenant_id' => 1,
                'status' => InventoryStatus::Posted->value,
                'item_id' => 10,
                'warehouse_id' => 2,
                'quantity' => '5.000000',
                'direction' => InventoryDirection::In->value,
                'movement_type' => InventoryMovementType::Receipt->value,
            ], true);
            $movement->syncOriginal();
            $movement->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('InventoryMovement is an immutable ledger and cannot be updated.');
            $movement->quantity = '100.000000';
            $movement->save();
        });
    }

    public function test_inventory_valuation_consumption_cannot_be_arbitrarily_tampered(): void
    {
        $this->withTenantExecutionContext(1, function (): void {
            $consumption = new InventoryValuationConsumption;
            $consumption->setRawAttributes([
                'tenant_id' => 1,
                'issue_movement_id' => 1,
                'valuation_layer_id' => 2,
                'quantity_consumed' => '5.000000',
                'unit_cost' => '10.000000',
                'total_cost' => '50.000000',
            ], true);
            $consumption->syncOriginal();
            $consumption->exists = true;

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('InventoryValuationConsumption is an immutable ledger and cannot be updated.');
            $consumption->total_cost = '999.000000';
            $consumption->save();
        });
    }
}
