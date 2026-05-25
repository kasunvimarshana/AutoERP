<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContractModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;

class CurrencyModel extends Model
{
    use HasActiveScope, HasReferenceScope, SoftDeletes;

    protected $table = 'currencies';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerModel::class, 'currency_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AccountModel::class, 'currency_id');
    }

    public function apTransactions(): HasMany
    {
        return $this->hasMany(ApTransactionModel::class, 'currency_id');
    }

    public function arTransactions(): HasMany
    {
        return $this->hasMany(ArTransactionModel::class, 'currency_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'currency_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccountModel::class, 'currency_id');
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContractModel::class, 'currency_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'currency_id');
    }

    public function invoiceReferences(): HasMany
    {
        return $this->hasMany(InvoiceReferenceModel::class, 'currency_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'currency_id');
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceListModel::class, 'currency_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'currency_id');
    }

    public function grnHeaders(): HasMany
    {
        return $this->hasMany(GrnHeaderModel::class, 'currency_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturnModel::class, 'currency_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrderModel::class, 'currency_id');
    }

    public function gdnHeaders(): HasMany
    {
        return $this->hasMany(GdnHeaderModel::class, 'currency_id');
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturnModel::class, 'currency_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(SupplierModel::class, 'currency_id');
    }

    public function tenantPlans(): HasMany
    {
        return $this->hasMany(TenantPlanModel::class, 'currency_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(TenantModel::class, 'currency_id');
    }

    public function vehicleServiceJobCards(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'currency_id');
    }
}

