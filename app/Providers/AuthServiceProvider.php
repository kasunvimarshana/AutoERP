<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Auth Service Provider
 *
 * Registers all authorization policies and gates for the application.
 * Maps entities to their corresponding policies for automatic discovery.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Sales Module
        \Modules\Sales\Entities\Customer::class => \Modules\Sales\Policies\CustomerPolicy::class,
        \Modules\Sales\Entities\Lead::class => \Modules\Sales\Policies\LeadPolicy::class,
        \Modules\Sales\Entities\SalesOrder::class => \Modules\Sales\Policies\SalesOrderPolicy::class,

        // Inventory Module
        \Modules\Inventory\Entities\Product::class => \Modules\Inventory\Policies\ProductPolicy::class,
        \Modules\Inventory\Entities\Warehouse::class => \Modules\Inventory\Policies\WarehousePolicy::class,
        \Modules\Inventory\Entities\StockMovement::class => \Modules\Inventory\Policies\StockMovementPolicy::class,

        // Accounting Module
        \Modules\Accounting\Entities\Account::class => \Modules\Accounting\Policies\AccountPolicy::class,
        \Modules\Accounting\Entities\Invoice::class => \Modules\Accounting\Policies\InvoicePolicy::class,
        \Modules\Accounting\Entities\JournalEntry::class => \Modules\Accounting\Policies\JournalEntryPolicy::class,
        \Modules\Accounting\Entities\Payment::class => \Modules\Accounting\Policies\PaymentPolicy::class,

        // HR Module
        \Modules\HR\Entities\Employee::class => \Modules\HR\Policies\EmployeePolicy::class,
        \Modules\HR\Entities\Leave::class => \Modules\HR\Policies\LeavePolicy::class,
        \Modules\HR\Entities\Payroll::class => \Modules\HR\Policies\PayrollPolicy::class,
        \Modules\HR\Entities\Attendance::class => \Modules\HR\Policies\AttendancePolicy::class,

        // Procurement Module
        \Modules\Procurement\Entities\Supplier::class => \Modules\Procurement\Policies\SupplierPolicy::class,
        \Modules\Procurement\Entities\PurchaseRequisition::class => \Modules\Procurement\Policies\PurchaseRequisitionPolicy::class,
        \Modules\Procurement\Entities\PurchaseOrder::class => \Modules\Procurement\Policies\PurchaseOrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates
        $this->defineGates();
    }

    /**
     * Define application-level gates.
     */
    protected function defineGates(): void
    {
        // Super admin gate - bypass all other authorization
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Tenant administration gate
        Gate::define('manage-tenant', function ($user) {
            return $user->hasPermissionTo('tenant.manage');
        });

        // Organization administration gate
        Gate::define('manage-organization', function ($user) {
            return $user->hasPermissionTo('organization.manage');
        });

        // Module management gates
        Gate::define('manage-modules', function ($user) {
            return $user->hasPermissionTo('modules.manage');
        });

        // Settings management gate
        Gate::define('manage-settings', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']) ||
                   $user->hasPermissionTo('settings.manage');
        });

        // User management gate
        Gate::define('manage-users', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']) ||
                   $user->hasPermissionTo('users.manage');
        });

        // Role management gate
        Gate::define('manage-roles', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']) ||
                   $user->hasPermissionTo('roles.manage');
        });

        // Permission management gate
        Gate::define('manage-permissions', function ($user) {
            return $user->hasRole('super-admin') ||
                   $user->hasPermissionTo('permissions.manage');
        });

        // Reports viewing gate
        Gate::define('view-reports', function ($user) {
            return $user->hasPermissionTo('reports.view');
        });

        // Advanced reports viewing gate
        Gate::define('view-advanced-reports', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager']) ||
                   $user->hasPermissionTo('reports.view-advanced');
        });

        // Export data gate
        Gate::define('export-data', function ($user) {
            return $user->hasPermissionTo('data.export');
        });

        // Import data gate
        Gate::define('import-data', function ($user) {
            return $user->hasPermissionTo('data.import');
        });

        // API access gate
        Gate::define('access-api', function ($user) {
            return $user->hasPermissionTo('api.access');
        });

        // Audit log viewing gate
        Gate::define('view-audit-logs', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']) ||
                   $user->hasPermissionTo('audit-logs.view');
        });

        // System monitoring gate
        Gate::define('monitor-system', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']) ||
                   $user->hasPermissionTo('system.monitor');
        });

        // Database access gate
        Gate::define('access-database', function ($user) {
            return $user->hasRole('super-admin') ||
                   $user->hasPermissionTo('database.access');
        });
    }
}
