<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContactModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicleModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\EntityAttributeModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankCategoryRuleModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankReconciliationModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CostCenterModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentTermModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceLogModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceRecordModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\BiometricDeviceModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContactModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContractModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeDocumentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeSalaryAssignmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\HolidayModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveAllocationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceCycleModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryComponentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftAssignmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PickingTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PutAwayTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ReceiptInspectionModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeGroupModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeValueModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemBrandModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifierModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeValueModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitTypeModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CashRegisterModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CheckModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentGroupModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\WriteOffModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Models\SequenceModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RolePermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDocumentModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserPermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserTenantModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeRunningChartModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorRunningChartModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceNonInventoryItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTypeModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\RecurringVoucherModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class OrganizationUnitModel extends Model
{
    use HasTenantScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'organization_units';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            '_lft' => 'integer',
            '_rgt' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitTypeModel::class, 'type_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AccountModel::class, 'organization_unit_id');
    }

    public function advancePaymentAllocations(): HasMany
    {
        return $this->hasMany(AdvancePaymentAllocationModel::class, 'organization_unit_id');
    }

    public function advancePayments(): HasMany
    {
        return $this->hasMany(AdvancePaymentModel::class, 'organization_unit_id');
    }

    public function apTransactions(): HasMany
    {
        return $this->hasMany(ApTransactionModel::class, 'organization_unit_id');
    }

    public function arTransactions(): HasMany
    {
        return $this->hasMany(ArTransactionModel::class, 'organization_unit_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AttachmentModel::class, 'organization_unit_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLogModel::class, 'organization_unit_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecordModel::class, 'organization_unit_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccountModel::class, 'organization_unit_id');
    }

    public function bankCategoryRules(): HasMany
    {
        return $this->hasMany(BankCategoryRuleModel::class, 'organization_unit_id');
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliationModel::class, 'organization_unit_id');
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransactionModel::class, 'organization_unit_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BatchModel::class, 'organization_unit_id');
    }

    public function biometricDevices(): HasMany
    {
        return $this->hasMany(BiometricDeviceModel::class, 'organization_unit_id');
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLineModel::class, 'organization_unit_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(BudgetModel::class, 'organization_unit_id');
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegisterModel::class, 'organization_unit_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(CheckModel::class, 'organization_unit_id');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboItemModel::class, 'organization_unit_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommentModel::class, 'organization_unit_id');
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenterModel::class, 'organization_unit_id');
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddressModel::class, 'organization_unit_id');
    }

    public function customerContacts(): HasMany
    {
        return $this->hasMany(CustomerContactModel::class, 'organization_unit_id');
    }

    public function customerPriceLists(): HasMany
    {
        return $this->hasMany(CustomerPriceListModel::class, 'organization_unit_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerModel::class, 'organization_unit_id');
    }

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleModel::class, 'organization_unit_id');
    }

    public function cycleCountHeaders(): HasMany
    {
        return $this->hasMany(CycleCountHeaderModel::class, 'organization_unit_id');
    }

    public function cycleCountLines(): HasMany
    {
        return $this->hasMany(CycleCountLineModel::class, 'organization_unit_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(DepartmentModel::class, 'organization_unit_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(DesignationModel::class, 'organization_unit_id');
    }

    public function employeeContacts(): HasMany
    {
        return $this->hasMany(EmployeeContactModel::class, 'organization_unit_id');
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContractModel::class, 'organization_unit_id');
    }

    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocumentModel::class, 'organization_unit_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeModel::class, 'organization_unit_id');
    }

    public function employeeSalaryAssignments(): HasMany
    {
        return $this->hasMany(EmployeeSalaryAssignmentModel::class, 'organization_unit_id');
    }

    public function employmentTypes(): HasMany
    {
        return $this->hasMany(EmploymentTypeModel::class, 'organization_unit_id');
    }

    public function entityAttributes(): HasMany
    {
        return $this->hasMany(EntityAttributeModel::class, 'organization_unit_id');
    }

    public function fiscalPeriods(): HasMany
    {
        return $this->hasMany(FiscalPeriodModel::class, 'organization_unit_id');
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYearModel::class, 'organization_unit_id');
    }

    public function gdnHeaders(): HasMany
    {
        return $this->hasMany(GdnHeaderModel::class, 'organization_unit_id');
    }

    public function gdnLines(): HasMany
    {
        return $this->hasMany(GdnLineModel::class, 'organization_unit_id');
    }

    public function grnHeaders(): HasMany
    {
        return $this->hasMany(GrnHeaderModel::class, 'organization_unit_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'organization_unit_id');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(HolidayModel::class, 'organization_unit_id');
    }

    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayerModel::class, 'organization_unit_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLineModel::class, 'organization_unit_id');
    }

    public function invoiceReferences(): HasMany
    {
        return $this->hasMany(InvoiceReferenceModel::class, 'organization_unit_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'organization_unit_id');
    }

    public function itemAttributeGroups(): HasMany
    {
        return $this->hasMany(ItemAttributeGroupModel::class, 'organization_unit_id');
    }

    public function itemAttributes(): HasMany
    {
        return $this->hasMany(ItemAttributeModel::class, 'organization_unit_id');
    }

    public function itemAttributeValues(): HasMany
    {
        return $this->hasMany(ItemAttributeValueModel::class, 'organization_unit_id');
    }

    public function itemBrands(): HasMany
    {
        return $this->hasMany(ItemBrandModel::class, 'organization_unit_id');
    }

    public function itemCategories(): HasMany
    {
        return $this->hasMany(ItemCategoryModel::class, 'organization_unit_id');
    }

    public function itemIdentifiers(): HasMany
    {
        return $this->hasMany(ItemIdentifierModel::class, 'organization_unit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemModel::class, 'organization_unit_id');
    }

    public function itemVariantAttributes(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeModel::class, 'organization_unit_id');
    }

    public function itemVariantAttributeValues(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeValueModel::class, 'organization_unit_id');
    }

    public function itemVariants(): HasMany
    {
        return $this->hasMany(ItemVariantModel::class, 'organization_unit_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntryModel::class, 'organization_unit_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'organization_unit_id');
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocationModel::class, 'organization_unit_id');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplicationModel::class, 'organization_unit_id');
    }

    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicyModel::class, 'organization_unit_id');
    }

    public function leavePolicyLines(): HasMany
    {
        return $this->hasMany(LeavePolicyLineModel::class, 'organization_unit_id');
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveTypeModel::class, 'organization_unit_id');
    }

    public function organizationUnitDocuments(): HasMany
    {
        return $this->hasMany(OrganizationUnitDocumentModel::class, 'organization_unit_id');
    }

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnitModel::class, 'parent_id');
    }

    public function organizationUnitSettingGroups(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingGroupModel::class, 'organization_unit_id');
    }

    public function organizationUnitSettings(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingModel::class, 'organization_unit_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocationModel::class, 'organization_unit_id');
    }

    public function paymentGroups(): HasMany
    {
        return $this->hasMany(PaymentGroupModel::class, 'organization_unit_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethodModel::class, 'organization_unit_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'organization_unit_id');
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTermModel::class, 'organization_unit_id');
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRunModel::class, 'organization_unit_id');
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLineModel::class, 'organization_unit_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayslipModel::class, 'organization_unit_id');
    }

    public function performanceCycles(): HasMany
    {
        return $this->hasMany(PerformanceCycleModel::class, 'organization_unit_id');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReviewModel::class, 'organization_unit_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(PermissionModel::class, 'organization_unit_id');
    }

    public function pickingTasks(): HasMany
    {
        return $this->hasMany(PickingTaskModel::class, 'organization_unit_id');
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItemModel::class, 'organization_unit_id');
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceListModel::class, 'organization_unit_id');
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'organization_unit_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'organization_unit_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'organization_unit_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturnModel::class, 'organization_unit_id');
    }

    public function putAwayTasks(): HasMany
    {
        return $this->hasMany(PutAwayTaskModel::class, 'organization_unit_id');
    }

    public function receiptInspections(): HasMany
    {
        return $this->hasMany(ReceiptInspectionModel::class, 'organization_unit_id');
    }

    public function recurringVouchers(): HasMany
    {
        return $this->hasMany(RecurringVoucherModel::class, 'organization_unit_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermissionModel::class, 'organization_unit_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(RoleModel::class, 'organization_unit_id');
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponentModel::class, 'organization_unit_id');
    }

    public function salaryStructureLines(): HasMany
    {
        return $this->hasMany(SalaryStructureLineModel::class, 'organization_unit_id');
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructureModel::class, 'organization_unit_id');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'organization_unit_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrderModel::class, 'organization_unit_id');
    }

    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'organization_unit_id');
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturnModel::class, 'organization_unit_id');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(SequenceModel::class, 'organization_unit_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(SerialModel::class, 'organization_unit_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignmentModel::class, 'organization_unit_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ShiftModel::class, 'organization_unit_id');
    }

    public function stockAdjustmentLines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLineModel::class, 'organization_unit_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustmentModel::class, 'organization_unit_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevelModel::class, 'organization_unit_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovementModel::class, 'organization_unit_id');
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservationModel::class, 'organization_unit_id');
    }

    public function stockTransferLines(): HasMany
    {
        return $this->hasMany(StockTransferLineModel::class, 'organization_unit_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransferModel::class, 'organization_unit_id');
    }

    public function supplierAddresses(): HasMany
    {
        return $this->hasMany(SupplierAddressModel::class, 'organization_unit_id');
    }

    public function supplierContacts(): HasMany
    {
        return $this->hasMany(SupplierContactModel::class, 'organization_unit_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItemModel::class, 'organization_unit_id');
    }

    public function supplierPriceLists(): HasMany
    {
        return $this->hasMany(SupplierPriceListModel::class, 'organization_unit_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(SupplierModel::class, 'organization_unit_id');
    }

    public function supplierVehicles(): HasMany
    {
        return $this->hasMany(SupplierVehicleModel::class, 'organization_unit_id');
    }

    public function systemUsers(): HasMany
    {
        return $this->hasMany(SystemUserModel::class, 'organization_unit_id');
    }

    public function taxGroups(): HasMany
    {
        return $this->hasMany(TaxGroupModel::class, 'organization_unit_id');
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRateModel::class, 'organization_unit_id');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRuleModel::class, 'organization_unit_id');
    }

    public function traceLogs(): HasMany
    {
        return $this->hasMany(TraceLogModel::class, 'organization_unit_id');
    }

    public function transferOrderLines(): HasMany
    {
        return $this->hasMany(TransferOrderLineModel::class, 'organization_unit_id');
    }

    public function transferOrders(): HasMany
    {
        return $this->hasMany(TransferOrderModel::class, 'organization_unit_id');
    }

    public function unitOfMeasures(): HasMany
    {
        return $this->hasMany(UnitOfMeasureModel::class, 'organization_unit_id');
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(UomConversionModel::class, 'organization_unit_id');
    }

    public function userDevices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'organization_unit_id');
    }

    public function userDocuments(): HasMany
    {
        return $this->hasMany(UserDocumentModel::class, 'organization_unit_id');
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermissionModel::class, 'organization_unit_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'organization_unit_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'organization_unit_id');
    }

    public function userTenants(): HasMany
    {
        return $this->hasMany(UserTenantModel::class, 'organization_unit_id');
    }

    public function valuationConfigs(): HasMany
    {
        return $this->hasMany(ValuationConfigModel::class, 'organization_unit_id');
    }

    public function vehicleDocuments(): HasMany
    {
        return $this->hasMany(VehicleDocumentModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLesseeAgreementCreditNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementCreditNoteModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLesseeAgreementDebitNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementDebitNoteModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLesseeAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLesseeRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeRunningChartModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLessorAgreementCreditNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementCreditNoteModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLessorAgreementDebitNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementDebitNoteModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLessorAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementModel::class, 'organization_unit_id');
    }

    public function vehicleRentalLessorRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorRunningChartModel::class, 'organization_unit_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'organization_unit_id');
    }

    public function vehicleServiceDiagnosticLines(): HasMany
    {
        return $this->hasMany(VehicleServiceDiagnosticLineModel::class, 'organization_unit_id');
    }

    public function vehicleServiceDiagnostics(): HasMany
    {
        return $this->hasMany(VehicleServiceDiagnosticModel::class, 'organization_unit_id');
    }

    public function vehicleServiceInspectionLines(): HasMany
    {
        return $this->hasMany(VehicleServiceInspectionLineModel::class, 'organization_unit_id');
    }

    public function vehicleServiceInspections(): HasMany
    {
        return $this->hasMany(VehicleServiceInspectionModel::class, 'organization_unit_id');
    }

    public function vehicleServiceJobCardLines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardLineModel::class, 'organization_unit_id');
    }

    public function vehicleServiceJobCards(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'organization_unit_id');
    }

    public function vehicleServiceLaborAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborAssignmentModel::class, 'organization_unit_id');
    }

    public function vehicleServiceLaborItems(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborItemModel::class, 'organization_unit_id');
    }

    public function vehicleServiceNonInventoryItems(): HasMany
    {
        return $this->hasMany(VehicleServiceNonInventoryItemModel::class, 'organization_unit_id');
    }

    public function vehicleServiceTypes(): HasMany
    {
        return $this->hasMany(VehicleServiceTypeModel::class, 'organization_unit_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(VoucherModel::class, 'organization_unit_id');
    }

    public function warehouseLocations(): HasMany
    {
        return $this->hasMany(WarehouseLocationModel::class, 'organization_unit_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(WarehouseModel::class, 'organization_unit_id');
    }

    public function writeOffs(): HasMany
    {
        return $this->hasMany(WriteOffModel::class, 'organization_unit_id');
    }

}
