<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Product\Models\Product;
use App\Modules\Inventory\Models\StockLedger;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Policies\UserPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockLedgerPolicy;
use App\Policies\TenantPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ContactPolicy;
use App\Policies\LeadPolicy;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Lead;
use App\Policies\VendorPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseReceiptPolicy;
use App\Policies\PurchaseReturnPolicy;
use App\Modules\Procurement\Models\Vendor;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\PurchaseReceipt;
use App\Modules\Procurement\Models\PurchaseReturn;
use App\Policies\QuotePolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\InvoicePolicy;
use App\Modules\Sales\Models\Quote;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Policies\PaymentPolicy;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Policies\POSPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Authorization Service Provider
 * 
 * Registers all policy mappings for the application.
 * Policies enforce fine-grained RBAC/ABAC with strict tenant isolation.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class,
        StockLedger::class => StockLedgerPolicy::class,
        Tenant::class => TenantPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Branch::class => BranchPolicy::class,
        Customer::class => CustomerPolicy::class,
        Contact::class => ContactPolicy::class,
        Lead::class => LeadPolicy::class,
        Vendor::class => VendorPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        PurchaseReceipt::class => PurchaseReceiptPolicy::class,
        PurchaseReturn::class => PurchaseReturnPolicy::class,
        Quote::class => QuotePolicy::class,
        SalesOrder::class => SalesOrderPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        POSSession::class => POSPolicy::class,
        POSTransaction::class => POSPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

