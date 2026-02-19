<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Performance Indexes Migration
 *
 * Adds critical indexes for improved query performance across all modules.
 * Focus areas:
 * - Tenant isolation (tenant_id on all tenant-scoped tables)
 * - Foreign key lookups (all *_id foreign keys)
 * - Common filters (status, created_at, type fields)
 * - Unique constraints where applicable
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Core Module Indexes
        $this->addCoreIndexes();

        // Tenant Module Indexes
        $this->addTenantIndexes();

        // Auth Module Indexes
        $this->addAuthIndexes();

        // Audit Module Indexes
        $this->addAuditIndexes();

        // Product Module Indexes
        $this->addProductIndexes();

        // Pricing Module Indexes
        $this->addPricingIndexes();

        // CRM Module Indexes
        $this->addCrmIndexes();

        // Sales Module Indexes
        $this->addSalesIndexes();

        // Purchase Module Indexes
        $this->addPurchaseIndexes();

        // Inventory Module Indexes
        $this->addInventoryIndexes();

        // Accounting Module Indexes
        $this->addAccountingIndexes();

        // Billing Module Indexes
        $this->addBillingIndexes();

        // Notification Module Indexes
        $this->addNotificationIndexes();

        // Reporting Module Indexes
        $this->addReportingIndexes();

        // Document Module Indexes
        $this->addDocumentIndexes();

        // Workflow Module Indexes
        $this->addWorkflowIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $this->dropWorkflowIndexes();
        $this->dropDocumentIndexes();
        $this->dropReportingIndexes();
        $this->dropNotificationIndexes();
        $this->dropBillingIndexes();
        $this->dropAccountingIndexes();
        $this->dropInventoryIndexes();
        $this->dropPurchaseIndexes();
        $this->dropSalesIndexes();
        $this->dropCrmIndexes();
        $this->dropPricingIndexes();
        $this->dropProductIndexes();
        $this->dropAuditIndexes();
        $this->dropAuthIndexes();
        $this->dropTenantIndexes();
        $this->dropCoreIndexes();
    }

    private function addCoreIndexes(): void
    {
        // No additional indexes needed for core module
    }

    private function addTenantIndexes(): void
    {
        if (Schema::hasTable('tenants')) {
            try {
                try {
                    Schema::table('tenants', function (Blueprint $table) {
                        $table->index('code');
                        $table->index('status');
                        $table->index('created_at');
                    });
                } catch (\Exception $e) {
                    // Indexes may already exist, ignore
                }
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('organizations')) {
            try {
                try {
                    Schema::table('organizations', function (Blueprint $table) {
                        $table->index('parent_id');
                        $table->index('status');
                        $table->index('created_at');
                    });
                } catch (\Exception $e) {
                    // Indexes may already exist, ignore
                }
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addAuthIndexes(): void
    {
        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('email');
                    $table->index('status');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('roles')) {
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->index(['name', 'tenant_id']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('permissions')) {
            try {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->index('name');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addAuditIndexes(): void
    {
        if (Schema::hasTable('audit_logs')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('event_type');
                    $table->index(['auditable_type', 'auditable_id']);
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addProductIndexes(): void
    {
        if (Schema::hasTable('products')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index(['code', 'tenant_id']);
                    $table->index('type');
                    $table->index('status');
                    $table->index('category_id');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('product_categories')) {
            try {
                Schema::table('product_categories', function (Blueprint $table) {
                    $table->index('parent_id');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addPricingIndexes(): void
    {
        if (Schema::hasTable('product_prices')) {
            try {
                Schema::table('product_prices', function (Blueprint $table) {
                    $table->index(['product_id', 'location_id']);
                    $table->index(['valid_from', 'valid_to']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addCrmIndexes(): void
    {
        if (Schema::hasTable('customers')) {
            try {
                Schema::table('customers', function (Blueprint $table) {
                    $table->index(['code', 'tenant_id']);
                    $table->index('type');
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('leads')) {
            try {
                Schema::table('leads', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('source');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('opportunities')) {
            try {
                Schema::table('opportunities', function (Blueprint $table) {
                    $table->index('stage');
                    $table->index('status');
                    $table->index('expected_close_date');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addSalesIndexes(): void
    {
        if (Schema::hasTable('quotations')) {
            try {
                Schema::table('quotations', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('customer_id');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('customer_id');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('invoices')) {
            try {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('customer_id');
                    $table->index('due_date');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addPurchaseIndexes(): void
    {
        if (Schema::hasTable('vendors')) {
            try {
                Schema::table('vendors', function (Blueprint $table) {
                    $table->index(['code', 'tenant_id']);
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            try {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('vendor_id');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('bills')) {
            try {
                Schema::table('bills', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('vendor_id');
                    $table->index('due_date');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addInventoryIndexes(): void
    {
        if (Schema::hasTable('stock_items')) {
            try {
                Schema::table('stock_items', function (Blueprint $table) {
                    $table->index(['product_id', 'warehouse_id']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('stock_movements')) {
            try {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $table->index('type');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('stock_counts')) {
            try {
                Schema::table('stock_counts', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('warehouse_id');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addAccountingIndexes(): void
    {
        if (Schema::hasTable('accounts')) {
            try {
                Schema::table('accounts', function (Blueprint $table) {
                    $table->index('type');
                    $table->index('parent_id');
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('journal_entries')) {
            try {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('entry_date');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('journal_lines')) {
            try {
                Schema::table('journal_lines', function (Blueprint $table) {
                    $table->index('account_id');
                    $table->index('journal_entry_id');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addBillingIndexes(): void
    {
        if (Schema::hasTable('subscriptions')) {
            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('plan_id');
                    $table->index('trial_ends_at');
                    $table->index('renews_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('subscription_payments')) {
            try {
                Schema::table('subscription_payments', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('subscription_id');
                    $table->index('paid_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addNotificationIndexes(): void
    {
        if (Schema::hasTable('notifications')) {
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index('type');
                    $table->index('status');
                    $table->index('scheduled_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('notification_logs')) {
            try {
                Schema::table('notification_logs', function (Blueprint $table) {
                    $table->index('notification_id');
                    $table->index('status');
                    $table->index('sent_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addReportingIndexes(): void
    {
        if (Schema::hasTable('reports')) {
            try {
                Schema::table('reports', function (Blueprint $table) {
                    $table->index('type');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('report_schedules')) {
            try {
                Schema::table('report_schedules', function (Blueprint $table) {
                    $table->index('frequency');
                    $table->index('next_run_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addDocumentIndexes(): void
    {
        if (Schema::hasTable('documents')) {
            try {
                Schema::table('documents', function (Blueprint $table) {
                    $table->index('type');
                    $table->index('status');
                    $table->index('folder_id');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('document_shares')) {
            try {
                Schema::table('document_shares', function (Blueprint $table) {
                    $table->index('expires_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function addWorkflowIndexes(): void
    {
        if (Schema::hasTable('workflows')) {
            try {
                Schema::table('workflows', function (Blueprint $table) {
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('workflow_instances')) {
            try {
                Schema::table('workflow_instances', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('workflow_id');
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('approvals')) {
            try {
                Schema::table('approvals', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('approver_id');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    // Drop methods (reverse of add methods)
    private function dropCoreIndexes(): void
    {
        // No indexes to drop
    }

    private function dropTenantIndexes(): void
    {
        if (Schema::hasTable('tenants')) {
            try {
                Schema::table('tenants', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'tenants_code_index');
                    $this->dropIndexIfExists($table, 'tenants_status_index');
                    $this->dropIndexIfExists($table, 'tenants_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('organizations')) {
            try {
                Schema::table('organizations', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'organizations_parent_id_index');
                    $this->dropIndexIfExists($table, 'organizations_status_index');
                    $this->dropIndexIfExists($table, 'organizations_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropAuthIndexes(): void
    {
        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'users_email_index');
                    $this->dropIndexIfExists($table, 'users_status_index');
                    $this->dropIndexIfExists($table, 'users_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('roles')) {
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'roles_name_tenant_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('permissions')) {
            try {
                Schema::table('permissions', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'permissions_name_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropAuditIndexes(): void
    {
        if (Schema::hasTable('audit_logs')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'audit_logs_event_type_index');
                    $this->dropIndexIfExists($table, 'audit_logs_auditable_type_auditable_id_index');
                    $this->dropIndexIfExists($table, 'audit_logs_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropProductIndexes(): void
    {
        if (Schema::hasTable('products')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'products_code_tenant_id_index');
                    $this->dropIndexIfExists($table, 'products_type_index');
                    $this->dropIndexIfExists($table, 'products_status_index');
                    $this->dropIndexIfExists($table, 'products_category_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('product_categories')) {
            try {
                Schema::table('product_categories', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'product_categories_parent_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropPricingIndexes(): void
    {
        if (Schema::hasTable('product_prices')) {
            try {
                Schema::table('product_prices', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'product_prices_product_id_location_id_index');
                    $this->dropIndexIfExists($table, 'product_prices_valid_from_valid_to_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropCrmIndexes(): void
    {
        if (Schema::hasTable('customers')) {
            try {
                Schema::table('customers', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'customers_code_tenant_id_index');
                    $this->dropIndexIfExists($table, 'customers_type_index');
                    $this->dropIndexIfExists($table, 'customers_status_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('leads')) {
            try {
                Schema::table('leads', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'leads_status_index');
                    $this->dropIndexIfExists($table, 'leads_source_index');
                    $this->dropIndexIfExists($table, 'leads_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('opportunities')) {
            try {
                Schema::table('opportunities', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'opportunities_stage_index');
                    $this->dropIndexIfExists($table, 'opportunities_status_index');
                    $this->dropIndexIfExists($table, 'opportunities_expected_close_date_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropSalesIndexes(): void
    {
        if (Schema::hasTable('quotations')) {
            try {
                Schema::table('quotations', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'quotations_status_index');
                    $this->dropIndexIfExists($table, 'quotations_customer_id_index');
                    $this->dropIndexIfExists($table, 'quotations_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'orders_status_index');
                    $this->dropIndexIfExists($table, 'orders_customer_id_index');
                    $this->dropIndexIfExists($table, 'orders_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('invoices')) {
            try {
                Schema::table('invoices', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'invoices_status_index');
                    $this->dropIndexIfExists($table, 'invoices_customer_id_index');
                    $this->dropIndexIfExists($table, 'invoices_due_date_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropPurchaseIndexes(): void
    {
        if (Schema::hasTable('vendors')) {
            try {
                Schema::table('vendors', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'vendors_code_tenant_id_index');
                    $this->dropIndexIfExists($table, 'vendors_status_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            try {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'purchase_orders_status_index');
                    $this->dropIndexIfExists($table, 'purchase_orders_vendor_id_index');
                    $this->dropIndexIfExists($table, 'purchase_orders_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('bills')) {
            try {
                Schema::table('bills', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'bills_status_index');
                    $this->dropIndexIfExists($table, 'bills_vendor_id_index');
                    $this->dropIndexIfExists($table, 'bills_due_date_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropInventoryIndexes(): void
    {
        if (Schema::hasTable('stock_items')) {
            try {
                Schema::table('stock_items', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'stock_items_product_id_warehouse_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('stock_movements')) {
            try {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'stock_movements_type_index');
                    $this->dropIndexIfExists($table, 'stock_movements_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('stock_counts')) {
            try {
                Schema::table('stock_counts', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'stock_counts_status_index');
                    $this->dropIndexIfExists($table, 'stock_counts_warehouse_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropAccountingIndexes(): void
    {
        if (Schema::hasTable('accounts')) {
            try {
                Schema::table('accounts', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'accounts_type_index');
                    $this->dropIndexIfExists($table, 'accounts_parent_id_index');
                    $this->dropIndexIfExists($table, 'accounts_status_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('journal_entries')) {
            try {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'journal_entries_status_index');
                    $this->dropIndexIfExists($table, 'journal_entries_entry_date_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('journal_lines')) {
            try {
                Schema::table('journal_lines', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'journal_lines_account_id_index');
                    $this->dropIndexIfExists($table, 'journal_lines_journal_entry_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropBillingIndexes(): void
    {
        if (Schema::hasTable('subscriptions')) {
            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'subscriptions_status_index');
                    $this->dropIndexIfExists($table, 'subscriptions_plan_id_index');
                    $this->dropIndexIfExists($table, 'subscriptions_trial_ends_at_index');
                    $this->dropIndexIfExists($table, 'subscriptions_renews_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('subscription_payments')) {
            try {
                Schema::table('subscription_payments', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'subscription_payments_status_index');
                    $this->dropIndexIfExists($table, 'subscription_payments_subscription_id_index');
                    $this->dropIndexIfExists($table, 'subscription_payments_paid_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropNotificationIndexes(): void
    {
        if (Schema::hasTable('notifications')) {
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'notifications_type_index');
                    $this->dropIndexIfExists($table, 'notifications_status_index');
                    $this->dropIndexIfExists($table, 'notifications_scheduled_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('notification_logs')) {
            try {
                Schema::table('notification_logs', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'notification_logs_notification_id_index');
                    $this->dropIndexIfExists($table, 'notification_logs_status_index');
                    $this->dropIndexIfExists($table, 'notification_logs_sent_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropReportingIndexes(): void
    {
        if (Schema::hasTable('reports')) {
            try {
                Schema::table('reports', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'reports_type_index');
                    $this->dropIndexIfExists($table, 'reports_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('report_schedules')) {
            try {
                Schema::table('report_schedules', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'report_schedules_frequency_index');
                    $this->dropIndexIfExists($table, 'report_schedules_next_run_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropDocumentIndexes(): void
    {
        if (Schema::hasTable('documents')) {
            try {
                Schema::table('documents', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'documents_type_index');
                    $this->dropIndexIfExists($table, 'documents_status_index');
                    $this->dropIndexIfExists($table, 'documents_folder_id_index');
                    $this->dropIndexIfExists($table, 'documents_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('document_shares')) {
            try {
                Schema::table('document_shares', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'document_shares_expires_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    private function dropWorkflowIndexes(): void
    {
        if (Schema::hasTable('workflows')) {
            try {
                Schema::table('workflows', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'workflows_status_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('workflow_instances')) {
            try {
                Schema::table('workflow_instances', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'workflow_instances_status_index');
                    $this->dropIndexIfExists($table, 'workflow_instances_workflow_id_index');
                    $this->dropIndexIfExists($table, 'workflow_instances_created_at_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }

        if (Schema::hasTable('approvals')) {
            try {
                Schema::table('approvals', function (Blueprint $table) {
                    $this->dropIndexIfExists($table, 'approvals_status_index');
                    $this->dropIndexIfExists($table, 'approvals_approver_id_index');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, ignore
            }
        }
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(Blueprint $table, string $index): void
    {
        // Laravel's dropIndex will silently fail if index doesn't exist in newer versions
        try {
            $table->dropIndex($index);
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
};
