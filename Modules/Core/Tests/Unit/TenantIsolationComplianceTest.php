<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use Modules\Core\Domain\Traits\HasTenant;
use PHPUnit\Framework\TestCase;

/**
 * Tenant isolation compliance tests.
 *
 * Verifies that every business-domain entity across all 19 modules uses
 * the HasTenant trait, which applies the TenantScope globally and
 * auto-assigns tenant_id on creation.
 *
 * Per AGENT.md §Multi-Tenancy (Critical — Zero Tolerance):
 *   "All new tables include `tenant_id` with global scope applied."
 *   "Tenant isolation must be enforced at the repository layer."
 *
 * Note: PluginManifest is intentionally excluded — it is a global
 * registry with no per-tenant data (plugin_manifests has no tenant_id
 * by design; only tenant_plugins is scoped).
 */
class TenantIsolationComplianceTest extends TestCase
{
    /**
     * Returns all business-domain entity class names that MUST use HasTenant.
     *
     * @return array<string, array{string}>
     */
    public static function tenantScopedEntityProvider(): array
    {
        return [
            // Auth
            'User'                     => [\Modules\Auth\Domain\Entities\User::class],
            // Organisation
            'Organisation'             => [\Modules\Organisation\Domain\Entities\Organisation::class],
            'Branch'                   => [\Modules\Organisation\Domain\Entities\Branch::class],
            'Location'                 => [\Modules\Organisation\Domain\Entities\Location::class],
            'Department'               => [\Modules\Organisation\Domain\Entities\Department::class],
            // Metadata
            'CustomFieldDefinition'    => [\Modules\Metadata\Domain\Entities\CustomFieldDefinition::class],
            'CustomFieldValue'         => [\Modules\Metadata\Domain\Entities\CustomFieldValue::class],
            'FeatureFlag'              => [\Modules\Metadata\Domain\Entities\FeatureFlag::class],
            // Workflow
            'WorkflowDefinition'       => [\Modules\Workflow\Domain\Entities\WorkflowDefinition::class],
            'WorkflowInstance'         => [\Modules\Workflow\Domain\Entities\WorkflowInstance::class],
            // Product
            'Product'                  => [\Modules\Product\Domain\Entities\Product::class],
            // Accounting
            'ChartOfAccount'           => [\Modules\Accounting\Domain\Entities\ChartOfAccount::class],
            'FiscalPeriod'             => [\Modules\Accounting\Domain\Entities\FiscalPeriod::class],
            'JournalEntry'             => [\Modules\Accounting\Domain\Entities\JournalEntry::class],
            // Pricing
            'PriceList'                => [\Modules\Pricing\Domain\Entities\PriceList::class],
            'DiscountRule'             => [\Modules\Pricing\Domain\Entities\DiscountRule::class],
            // Inventory
            'Warehouse'                => [\Modules\Inventory\Domain\Entities\Warehouse::class],
            'StockItem'                => [\Modules\Inventory\Domain\Entities\StockItem::class],
            'StockTransaction'         => [\Modules\Inventory\Domain\Entities\StockTransaction::class],
            'StockReservation'         => [\Modules\Inventory\Domain\Entities\StockReservation::class],
            // Warehouse
            'WarehouseZone'            => [\Modules\Warehouse\Domain\Entities\WarehouseZone::class],
            'BinLocation'              => [\Modules\Warehouse\Domain\Entities\BinLocation::class],
            'PickingOrder'             => [\Modules\Warehouse\Domain\Entities\PickingOrder::class],
            // Sales
            'SalesOrder'               => [\Modules\Sales\Domain\Entities\SalesOrder::class],
            'SalesOrderLine'           => [\Modules\Sales\Domain\Entities\SalesOrderLine::class],
            'SalesInvoice'             => [\Modules\Sales\Domain\Entities\SalesInvoice::class],
            // POS
            'PosTerminal'              => [\Modules\POS\Domain\Entities\PosTerminal::class],
            'PosSession'               => [\Modules\POS\Domain\Entities\PosSession::class],
            'PosTransaction'           => [\Modules\POS\Domain\Entities\PosTransaction::class],
            // CRM
            'CrmLead'                  => [\Modules\CRM\Domain\Entities\CrmLead::class],
            'CrmOpportunity'           => [\Modules\CRM\Domain\Entities\CrmOpportunity::class],
            // Procurement
            'Vendor'                   => [\Modules\Procurement\Domain\Entities\Vendor::class],
            'PurchaseOrder'            => [\Modules\Procurement\Domain\Entities\PurchaseOrder::class],
            'GoodsReceipt'             => [\Modules\Procurement\Domain\Entities\GoodsReceipt::class],
            // Reporting
            'ReportDefinition'         => [\Modules\Reporting\Domain\Entities\ReportDefinition::class],
            'ReportExport'             => [\Modules\Reporting\Domain\Entities\ReportExport::class],
            // Notification
            'NotificationTemplate'     => [\Modules\Notification\Domain\Entities\NotificationTemplate::class],
            'NotificationLog'          => [\Modules\Notification\Domain\Entities\NotificationLog::class],
            // Integration
            'WebhookEndpoint'          => [\Modules\Integration\Domain\Entities\WebhookEndpoint::class],
            'IntegrationLog'           => [\Modules\Integration\Domain\Entities\IntegrationLog::class],
            // Plugin (tenant-scoped side)
            'TenantPlugin'             => [\Modules\Plugin\Domain\Entities\TenantPlugin::class],
        ];
    }

    /**
     * @dataProvider tenantScopedEntityProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tenantScopedEntityProvider')]
    public function test_entity_uses_has_tenant_trait(string $entityClass): void
    {
        $traits = class_uses_recursive($entityClass);

        $this->assertContains(
            HasTenant::class,
            $traits,
            sprintf(
                'Entity %s must use the HasTenant trait to enforce tenant isolation. '
                . 'This is a Critical Violation per AGENT.md §Multi-Tenancy.',
                $entityClass,
            ),
        );
    }

    /**
     * PluginManifest is the one entity that must NOT have HasTenant —
     * it is a global registry shared across all tenants.
     */
    public function test_plugin_manifest_does_not_use_has_tenant(): void
    {
        $traits = class_uses_recursive(\Modules\Plugin\Domain\Entities\PluginManifest::class);

        $this->assertNotContains(
            HasTenant::class,
            $traits,
            'PluginManifest is a global registry and must NOT be tenant-scoped.',
        );
    }

    /**
     * HasTenant trait itself exposes a bootHasTenant() method,
     * which Eloquent calls automatically when the model boots.
     */
    public function test_has_tenant_trait_exposes_boot_method(): void
    {
        $this->assertTrue(
            method_exists(HasTenant::class, 'bootHasTenant'),
            'HasTenant::bootHasTenant() must exist for Eloquent automatic trait boot.',
        );
    }

    /**
     * HasTenant exposes withoutTenantScope() for admin-only bypass operations.
     */
    public function test_has_tenant_trait_exposes_without_tenant_scope(): void
    {
        $this->assertTrue(
            method_exists(HasTenant::class, 'withoutTenantScope'),
            'HasTenant::withoutTenantScope() must exist for authorised scope bypass.',
        );
    }
}
