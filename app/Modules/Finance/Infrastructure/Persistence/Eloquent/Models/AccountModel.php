<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankCategoryRuleModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryComponentModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CashRegisterModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceNonInventoryItemModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\RecurringVoucherModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherModel;

class AccountModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'accounts';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'allows_manual_posting' => 'boolean',
            'is_active' => 'boolean',
            'is_bank_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'is_control_account' => 'boolean',
            'is_system' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AccountModel::class, 'parent_id');
    }

    public function apTransactions(): HasMany
    {
        return $this->hasMany(ApTransactionModel::class, 'account_id');
    }

    public function arTransactions(): HasMany
    {
        return $this->hasMany(ArTransactionModel::class, 'account_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccountModel::class, 'account_id');
    }

    public function bankCategoryRules(): HasMany
    {
        return $this->hasMany(BankCategoryRuleModel::class, 'account_id');
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLineModel::class, 'account_id');
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegisterModel::class, 'cash_account_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerModel::class, 'ar_account_id');
    }

    public function gdnLines(): HasMany
    {
        return $this->hasMany(GdnLineModel::class, 'account_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'account_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLineModel::class, 'account_id');
    }

    public function invoiceReferences(): HasMany
    {
        return $this->hasMany(InvoiceReferenceModel::class, 'ar_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'ap_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemModel::class, 'inventory_loss_account_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'account_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethodModel::class, 'account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'account_id');
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'account_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'account_id');
    }

    public function recurringVouchers(): HasMany
    {
        return $this->hasMany(RecurringVoucherModel::class, 'contra_account_id');
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponentModel::class, 'account_id');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'account_id');
    }

    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'account_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(SupplierModel::class, 'ap_account_id');
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRateModel::class, 'account_id');
    }

    public function vehicleRentalLesseeAgreementCreditNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementCreditNoteModel::class, 'account_id');
    }

    public function vehicleRentalLesseeAgreementDebitNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementDebitNoteModel::class, 'account_id');
    }

    public function vehicleRentalLesseeAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementModel::class, 'rental_expense_account_id');
    }

    public function vehicleRentalLessorAgreementCreditNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementCreditNoteModel::class, 'account_id');
    }

    public function vehicleRentalLessorAgreementDebitNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementDebitNoteModel::class, 'account_id');
    }

    public function vehicleRentalLessorAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementModel::class, 'rental_income_account_id');
    }

    public function vehicleServiceJobCardLines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardLineModel::class, 'account_id');
    }

    public function vehicleServiceLaborItems(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborItemModel::class, 'account_id');
    }

    public function vehicleServiceNonInventoryItems(): HasMany
    {
        return $this->hasMany(VehicleServiceNonInventoryItemModel::class, 'account_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(VoucherModel::class, 'account_id');
    }

}
