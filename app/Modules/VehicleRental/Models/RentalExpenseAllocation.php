<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\Invoice\Models\Invoice;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;

final class RentalExpenseAllocation extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_expense_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'expense_id' => 'integer',
            'sequence' => 'integer',
            'allocation_type' => RentalExpenseAllocationType::class,
            'target_customer_agreement_id' => 'integer',
            'target_owner_agreement_id' => 'integer',
            'target_vehicle_use_id' => 'integer',
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'employee_id' => 'integer',
            'net_amount' => 'decimal:6',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:6',
            'withholding_amount' => 'decimal:6',
            'markup_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'invoice_id' => 'integer',
        ]);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(RentalExpense::class, 'expense_id');
    }

    public function targetCustomerAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class, 'target_customer_agreement_id');
    }

    public function targetOwnerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class, 'target_owner_agreement_id');
    }

    public function targetVehicleUse(): BelongsTo
    {
        return $this->belongsTo(VehicleUse::class, 'target_vehicle_use_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id')->withTrashed();
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'tax_group_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
