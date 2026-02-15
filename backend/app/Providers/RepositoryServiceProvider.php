<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repositories
use App\Modules\Tenant\Repositories\TenantRepositoryInterface;
use App\Modules\Tenant\Repositories\TenantRepository;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepositoryInterface;
use App\Modules\Inventory\Repositories\StockLedgerRepository;

// Models (for repository injection)
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Product\Models\Product;
use App\Modules\Inventory\Models\StockLedger;
use App\Modules\MasterData\Models\Currency;
use App\Modules\MasterData\Models\UnitOfMeasure;
use App\Modules\MasterData\Models\TaxRate;

// Master Data Repositories
use App\Modules\MasterData\Repositories\CurrencyRepository;
use App\Modules\MasterData\Repositories\UnitOfMeasureRepository;
use App\Modules\MasterData\Repositories\TaxRateRepository;

// CRM Repositories
use App\Modules\CRM\Repositories\CustomerRepositoryInterface;
use App\Modules\CRM\Repositories\CustomerRepository;
use App\Modules\CRM\Repositories\ContactRepositoryInterface;
use App\Modules\CRM\Repositories\ContactRepository;
use App\Modules\CRM\Repositories\LeadRepositoryInterface;
use App\Modules\CRM\Repositories\LeadRepository;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Lead;

// Procurement Repositories
use App\Modules\Procurement\Repositories\VendorRepositoryInterface;
use App\Modules\Procurement\Repositories\VendorRepository;
use App\Modules\Procurement\Repositories\PurchaseOrderRepositoryInterface;
use App\Modules\Procurement\Repositories\PurchaseOrderRepository;
use App\Modules\Procurement\Repositories\PurchaseReceiptRepositoryInterface;
use App\Modules\Procurement\Repositories\PurchaseReceiptRepository;
use App\Modules\Procurement\Models\Vendor;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\PurchaseReceipt;

// Sales Repositories
use App\Modules\Sales\Repositories\QuoteRepositoryInterface;
use App\Modules\Sales\Repositories\QuoteRepository;
use App\Modules\Sales\Repositories\SalesOrderRepositoryInterface;
use App\Modules\Sales\Repositories\SalesOrderRepository;
use App\Modules\Sales\Models\Quote;
use App\Modules\Sales\Models\SalesOrder;

// Invoice Repositories
use App\Modules\Invoice\Repositories\InvoiceRepositoryInterface;
use App\Modules\Invoice\Repositories\InvoiceRepository;
use App\Modules\Invoice\Repositories\InvoicePaymentRepositoryInterface;
use App\Modules\Invoice\Repositories\InvoicePaymentRepository;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoicePayment;

// Payment Repositories
use App\Modules\Payment\Repositories\PaymentRepositoryInterface;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Repositories\PaymentMethodRepositoryInterface;
use App\Modules\Payment\Repositories\PaymentMethodRepository;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentMethod;

// POS Repositories
use App\Modules\POS\Repositories\POSSessionRepositoryInterface;
use App\Modules\POS\Repositories\POSSessionRepository;
use App\Modules\POS\Repositories\POSTransactionRepositoryInterface;
use App\Modules\POS\Repositories\POSTransactionRepository;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;

// IAM Repositories
use App\Modules\IAM\Repositories\UserRepositoryInterface;
use App\Modules\IAM\Repositories\UserRepository;
use App\Models\User;

// Manufacturing Repositories
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepositoryInterface;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepository;
use App\Modules\Manufacturing\Repositories\WorkOrderRepositoryInterface;
use App\Modules\Manufacturing\Repositories\WorkOrderRepository;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\WorkOrder;

// Warehouse Repositories
use App\Modules\Warehouse\Repositories\WarehouseTransferRepositoryInterface;
use App\Modules\Warehouse\Repositories\WarehouseTransferRepository;
use App\Modules\Warehouse\Repositories\WarehousePickingRepositoryInterface;
use App\Modules\Warehouse\Repositories\WarehousePickingRepository;
use App\Modules\Warehouse\Repositories\WarehousePutawayRepositoryInterface;
use App\Modules\Warehouse\Repositories\WarehousePutawayRepository;
use App\Modules\Warehouse\Models\WarehouseTransfer;
use App\Modules\Warehouse\Models\WarehousePicking;
use App\Modules\Warehouse\Models\WarehousePutaway;

// Taxation Repositories
use App\Modules\Taxation\Repositories\TaxGroupRepository;
use App\Modules\Taxation\Repositories\TaxExemptionRepository;
use App\Modules\Taxation\Repositories\TaxJurisdictionRepository;
use App\Modules\Taxation\Repositories\TaxCalculationRepository;
use App\Modules\Taxation\Models\TaxGroup;
use App\Modules\Taxation\Models\TaxExemption;
use App\Modules\Taxation\Models\TaxJurisdiction;
use App\Modules\Taxation\Models\TaxCalculation;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Tenant Repository
        $this->app->bind(TenantRepositoryInterface::class, function ($app) {
            return new TenantRepository(new Tenant());
        });

        // Product Repository
        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new ProductRepository(new Product());
        });

        // Stock Ledger Repository
        $this->app->bind(StockLedgerRepositoryInterface::class, function ($app) {
            return new StockLedgerRepository(new StockLedger());
        });

        // Master Data Repositories
        $this->app->singleton(CurrencyRepository::class, function ($app) {
            return new CurrencyRepository(new Currency());
        });

        $this->app->singleton(UnitOfMeasureRepository::class, function ($app) {
            return new UnitOfMeasureRepository(new UnitOfMeasure());
        });

        $this->app->singleton(TaxRateRepository::class, function ($app) {
            return new TaxRateRepository(new TaxRate());
        });

        // CRM Repositories
        $this->app->bind(CustomerRepositoryInterface::class, function ($app) {
            return new CustomerRepository(new Customer());
        });

        $this->app->bind(ContactRepositoryInterface::class, function ($app) {
            return new ContactRepository(new Contact());
        });

        $this->app->bind(LeadRepositoryInterface::class, function ($app) {
            return new LeadRepository(new Lead());
        });

        // Procurement Repositories
        $this->app->bind(VendorRepositoryInterface::class, function ($app) {
            return new VendorRepository(new Vendor());
        });

        $this->app->bind(PurchaseOrderRepositoryInterface::class, function ($app) {
            return new PurchaseOrderRepository(new PurchaseOrder());
        });

        $this->app->bind(PurchaseReceiptRepositoryInterface::class, function ($app) {
            return new PurchaseReceiptRepository(new PurchaseReceipt());
        });

        // Sales Repositories
        $this->app->bind(QuoteRepositoryInterface::class, function ($app) {
            return new QuoteRepository(new Quote());
        });

        $this->app->bind(SalesOrderRepositoryInterface::class, function ($app) {
            return new SalesOrderRepository(new SalesOrder());
        });

        // Invoice Repositories
        $this->app->bind(InvoiceRepositoryInterface::class, function ($app) {
            return new InvoiceRepository(new Invoice());
        });

        $this->app->bind(InvoicePaymentRepositoryInterface::class, function ($app) {
            return new InvoicePaymentRepository(new InvoicePayment());
        });

        // Payment Repositories
        $this->app->bind(PaymentRepositoryInterface::class, function ($app) {
            return new PaymentRepository(new Payment());
        });

        $this->app->bind(PaymentMethodRepositoryInterface::class, function ($app) {
            return new PaymentMethodRepository(new PaymentMethod());
        });

        // POS Repositories
        $this->app->bind(POSSessionRepositoryInterface::class, function ($app) {
            return new POSSessionRepository(new POSSession());
        });

        $this->app->bind(POSTransactionRepositoryInterface::class, function ($app) {
            return new POSTransactionRepository(new POSTransaction());
        });

        // IAM Repositories
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Manufacturing Repositories
        $this->app->bind(BillOfMaterialRepositoryInterface::class, function ($app) {
            return new BillOfMaterialRepository(new BillOfMaterial());
        });

        $this->app->bind(WorkOrderRepositoryInterface::class, function ($app) {
            return new WorkOrderRepository(new WorkOrder());
        });

        // Warehouse Repositories
        $this->app->bind(WarehouseTransferRepositoryInterface::class, function ($app) {
            return new WarehouseTransferRepository(new WarehouseTransfer());
        });

        $this->app->bind(WarehousePickingRepositoryInterface::class, function ($app) {
            return new WarehousePickingRepository(new WarehousePicking());
        });

        $this->app->bind(WarehousePutawayRepositoryInterface::class, function ($app) {
            return new WarehousePutawayRepository(new WarehousePutaway());
        });

        // Taxation Repositories
        $this->app->singleton(TaxGroupRepository::class, function ($app) {
            return new TaxGroupRepository(new TaxGroup());
        });

        $this->app->singleton(TaxExemptionRepository::class, function ($app) {
            return new TaxExemptionRepository(new TaxExemption());
        });

        $this->app->singleton(TaxJurisdictionRepository::class, function ($app) {
            return new TaxJurisdictionRepository(new TaxJurisdiction());
        });

        $this->app->singleton(TaxCalculationRepository::class, function ($app) {
            return new TaxCalculationRepository(new TaxCalculation());
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
