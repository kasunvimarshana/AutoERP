<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Core\Application\Contracts\AuditServiceInterface;
use Modules\Core\Application\DTOs\AuditLogData;
use Modules\Core\Application\Services\AuditService;
use Modules\Core\Domain\Entities\AuditLog;
use Modules\Core\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Core\Domain\ValueObjects\AuditAction;
use Modules\Core\Infrastructure\Concerns\Auditable;
use Modules\Core\Infrastructure\Http\Resources\AuditLogResource;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditRepository;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use PHPUnit\Framework\TestCase;

// Module models — used to verify HasAudit presence
use Modules\Account\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Location\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

// WIMS module models — used to verify HasAudit for full audit compliance
// Returns
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnLineModel;
// Inbound flow
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;
// Outbound flow
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;
// Stock movement
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
// Inventory
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySerialNumberModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLocationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySettingModel;
// GS1 traceability
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1IdentifierModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1BarcodeModel;
// Warehouse zone
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseZoneModel;
// UoM module
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\ProductUomSettingModel;

class AuditTraitTest extends TestCase
{
    // ── AuditAction Value Object ───────────────────────────────────────────────

    public function test_audit_action_class_exists(): void
    {
        $this->assertTrue(class_exists(AuditAction::class));
    }

    public function test_audit_action_built_in_constants(): void
    {
        $this->assertSame('created',  AuditAction::CREATED);
        $this->assertSame('updated',  AuditAction::UPDATED);
        $this->assertSame('deleted',  AuditAction::DELETED);
        $this->assertSame('restored', AuditAction::RESTORED);
        $this->assertSame('custom',   AuditAction::CUSTOM);
    }

    public function test_audit_action_from_valid_value(): void
    {
        $action = AuditAction::from(AuditAction::CREATED);
        $this->assertSame('created', $action->value());
    }

    public function test_audit_action_from_invalid_value_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuditAction::from('nonexistent_action');
    }

    public function test_audit_action_register_custom_action(): void
    {
        AuditAction::register('exported');
        $action = AuditAction::from('exported');
        $this->assertSame('exported', $action->value());
    }

    public function test_audit_action_from_database_skips_validation(): void
    {
        $action = AuditAction::fromDatabase('legacy_event');
        $this->assertSame('legacy_event', $action->value());
    }

    public function test_audit_action_all_returns_registered_actions(): void
    {
        $all = AuditAction::all();
        $this->assertContains(AuditAction::CREATED, $all);
        $this->assertContains(AuditAction::UPDATED, $all);
        $this->assertContains(AuditAction::DELETED, $all);
        $this->assertContains(AuditAction::RESTORED, $all);
        $this->assertContains(AuditAction::CUSTOM, $all);
    }

    public function test_audit_action_equals(): void
    {
        $a = AuditAction::from(AuditAction::CREATED);
        $b = AuditAction::from(AuditAction::CREATED);
        $c = AuditAction::from(AuditAction::UPDATED);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_audit_action_to_string(): void
    {
        $action = AuditAction::from(AuditAction::UPDATED);
        $this->assertSame('updated', (string) $action);
    }

    // ── AuditLog Entity ───────────────────────────────────────────────────────

    public function test_audit_log_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(AuditLog::class));
    }

    public function test_audit_log_entity_can_be_constructed(): void
    {
        $now = new \DateTimeImmutable;
        $log = new AuditLog(
            id: 1,
            tenantId: 10,
            userId: 5,
            event: AuditAction::from(AuditAction::CREATED),
            auditableType: 'AccountModel',
            auditableId: 42,
            oldValues: null,
            newValues: ['name' => 'Cash'],
            url: 'https://example.com/api/accounts',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
            tags: ['finance'],
            metadata: ['source' => 'test'],
            createdAt: $now,
        );

        $this->assertSame(1, $log->getId());
        $this->assertSame(10, $log->getTenantId());
        $this->assertSame(5, $log->getUserId());
        $this->assertSame('created', $log->getEvent()->value());
        $this->assertSame('AccountModel', $log->getAuditableType());
        $this->assertSame(42, $log->getAuditableId());
        $this->assertNull($log->getOldValues());
        $this->assertSame(['name' => 'Cash'], $log->getNewValues());
        $this->assertSame('https://example.com/api/accounts', $log->getUrl());
        $this->assertSame('127.0.0.1', $log->getIpAddress());
        $this->assertSame('PHPUnit', $log->getUserAgent());
        $this->assertSame(['finance'], $log->getTags());
        $this->assertSame(['source' => 'test'], $log->getMetadata());
        $this->assertSame($now, $log->getCreatedAt());
    }

    public function test_audit_log_has_changes_true_when_new_values_present(): void
    {
        $log = $this->makeAuditLog(oldValues: null, newValues: ['name' => 'Cash']);
        $this->assertTrue($log->hasChanges());
    }

    public function test_audit_log_has_changes_false_when_both_null(): void
    {
        $log = $this->makeAuditLog(oldValues: null, newValues: null);
        $this->assertFalse($log->hasChanges());
    }

    public function test_audit_log_get_diff_returns_changed_keys(): void
    {
        $log = $this->makeAuditLog(
            oldValues: ['name' => 'Old Cash', 'status' => 'active'],
            newValues: ['name' => 'New Cash', 'status' => 'active'],
        );

        $diff = $log->getDiff();

        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayNotHasKey('status', $diff);
        $this->assertSame('Old Cash', $diff['name']['old']);
        $this->assertSame('New Cash', $diff['name']['new']);
    }

    public function test_audit_log_get_diff_includes_added_keys(): void
    {
        $log = $this->makeAuditLog(
            oldValues: [],
            newValues: ['description' => 'New desc'],
        );

        $diff = $log->getDiff();

        $this->assertArrayHasKey('description', $diff);
        $this->assertNull($diff['description']['old']);
        $this->assertSame('New desc', $diff['description']['new']);
    }

    // ── AuditLogData DTO ──────────────────────────────────────────────────────

    public function test_audit_log_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(AuditLogData::class));
    }

    public function test_audit_log_data_dto_can_be_created_from_array(): void
    {
        $dto = AuditLogData::fromArray([
            'event'          => 'created',
            'auditable_type' => 'AccountModel',
            'auditable_id'   => '42',
            'tenant_id'      => 1,
            'user_id'        => 2,
            'old_values'     => null,
            'new_values'     => ['name' => 'Cash'],
        ]);

        $this->assertSame('created', $dto->event);
        $this->assertSame('AccountModel', $dto->auditable_type);
        $this->assertSame('42', $dto->auditable_id);
        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame(2, $dto->user_id);
        $this->assertNull($dto->old_values);
        $this->assertSame(['name' => 'Cash'], $dto->new_values);
    }

    public function test_audit_log_data_dto_defaults(): void
    {
        $dto = new AuditLogData;

        $this->assertSame('custom', $dto->event);
        $this->assertSame('', $dto->auditable_type);
        $this->assertSame('', $dto->auditable_id);
        $this->assertNull($dto->tenant_id);
        $this->assertNull($dto->user_id);
        $this->assertNull($dto->old_values);
        $this->assertNull($dto->new_values);
        $this->assertNull($dto->url);
        $this->assertNull($dto->ip_address);
        $this->assertNull($dto->user_agent);
        $this->assertNull($dto->tags);
        $this->assertNull($dto->metadata);
    }

    // ── AuditServiceInterface contract ────────────────────────────────────────

    public function test_audit_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AuditServiceInterface::class));
    }

    public function test_audit_service_implements_interface(): void
    {
        $this->assertTrue(
            in_array(AuditServiceInterface::class, class_implements(AuditService::class), true)
        );
    }

    // ── AuditRepositoryInterface contract ─────────────────────────────────────

    public function test_audit_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AuditRepositoryInterface::class));
    }

    public function test_eloquent_audit_repository_implements_interface(): void
    {
        $this->assertTrue(
            in_array(AuditRepositoryInterface::class, class_implements(EloquentAuditRepository::class), true)
        );
    }

    // ── AuditService behaviour (using a stub repository) ─────────────────────

    public function test_audit_service_record_delegates_to_repository(): void
    {
        $expectedLog = $this->makeAuditLog();
        $repo = $this->createMock(AuditRepositoryInterface::class);
        $repo->expects($this->once())
             ->method('record')
             ->willReturn($expectedLog);

        $service = new AuditService($repo);
        $result = $service->record([
            'event'          => 'created',
            'auditable_type' => 'AccountModel',
            'auditable_id'   => '42',
        ]);

        $this->assertSame($expectedLog, $result);
    }

    public function test_audit_service_find_delegates_to_repository(): void
    {
        $expectedLog = $this->makeAuditLog();
        $repo = $this->createMock(AuditRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(1)->willReturn($expectedLog);

        $service = new AuditService($repo);
        $this->assertSame($expectedLog, $service->find(1));
    }

    public function test_audit_service_prune_delegates_to_repository(): void
    {
        $before = new \DateTimeImmutable('-30 days');
        $repo = $this->createMock(AuditRepositoryInterface::class);
        $repo->expects($this->once())->method('pruneOlderThan')->with($before)->willReturn(15);

        $service = new AuditService($repo);
        $this->assertSame(15, $service->pruneOlderThan($before));
    }

    // ── AuditLogModel ─────────────────────────────────────────────────────────

    public function test_audit_log_model_class_exists(): void
    {
        $this->assertTrue(class_exists(AuditLogModel::class));
    }

    public function test_audit_log_model_has_correct_table(): void
    {
        $model = new AuditLogModel;
        $this->assertSame('audit_logs', $model->getTable());
    }

    public function test_audit_log_model_has_no_updated_at(): void
    {
        $model = new AuditLogModel;
        $this->assertNull($model->getUpdatedAtColumn());
    }

    // ── HasAudit Eloquent trait ───────────────────────────────────────────────

    public function test_has_audit_trait_exists(): void
    {
        $this->assertTrue(trait_exists(HasAudit::class));
    }

    public function test_has_audit_trait_defines_audit_logs_method(): void
    {
        $methods = get_class_methods(HasAudit::class);
        $this->assertContains('auditLogs', $methods);
    }

    public function test_has_audit_trait_defines_without_audit_method(): void
    {
        $methods = get_class_methods(HasAudit::class);
        $this->assertContains('withoutAudit', $methods);
    }

    public function test_has_audit_trait_defines_filter_auditable_attributes_method(): void
    {
        $methods = get_class_methods(HasAudit::class);
        $this->assertContains('filterAuditableAttributes', $methods);
    }

    public function test_has_audit_trait_defines_get_audit_exclude_method(): void
    {
        $methods = get_class_methods(HasAudit::class);
        $this->assertContains('getAuditExclude', $methods);
    }

    public function test_has_audit_trait_defines_get_audit_include_method(): void
    {
        $methods = get_class_methods(HasAudit::class);
        $this->assertContains('getAuditInclude', $methods);
    }

    // ── Auditable service trait ───────────────────────────────────────────────

    public function test_auditable_trait_exists(): void
    {
        $this->assertTrue(trait_exists(Auditable::class));
    }

    public function test_auditable_trait_defines_record_audit_method(): void
    {
        $ref = new \ReflectionClass(Auditable::class);
        $methodNames = array_map(fn (\ReflectionMethod $m) => $m->getName(), $ref->getMethods());
        $this->assertContains('recordAudit', $methodNames);
    }

    // ── AuditLogResource ──────────────────────────────────────────────────────

    public function test_audit_log_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(AuditLogResource::class));
    }

    // ── Module models carry HasAudit ──────────────────────────────────────────

    public function test_account_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(AccountModel::class));
    }

    public function test_brand_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(BrandModel::class));
    }

    public function test_category_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(CategoryModel::class));
    }

    public function test_customer_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(CustomerModel::class));
    }

    public function test_location_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(LocationModel::class));
    }

    public function test_organization_unit_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(OrganizationUnitModel::class));
    }

    public function test_product_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(ProductModel::class));
    }

    public function test_supplier_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(SupplierModel::class));
    }

    public function test_tenant_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(TenantModel::class));
    }

    public function test_user_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(UserModel::class));
    }

    public function test_warehouse_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(WarehouseModel::class));
    }

    // ── WIMS module models HasAudit (full audit compliance) ───────────────────
    // ── WIMS — Returns module ─────────────────────────────────────────────────

    public function test_stock_return_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(StockReturnModel::class));
    }

    public function test_stock_return_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(StockReturnLineModel::class));
    }

    public function test_stock_movement_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(StockMovementModel::class));
    }

    // ── WIMS — Inbound flow ───────────────────────────────────────────────────

    public function test_purchase_order_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(PurchaseOrderModel::class));
    }

    public function test_purchase_order_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(PurchaseOrderLineModel::class));
    }

    public function test_goods_receipt_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(GoodsReceiptModel::class));
    }

    public function test_goods_receipt_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(GoodsReceiptLineModel::class));
    }

    // ── WIMS — Outbound flow ──────────────────────────────────────────────────

    public function test_sales_order_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(SalesOrderModel::class));
    }

    public function test_sales_order_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(SalesOrderLineModel::class));
    }

    public function test_dispatch_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(DispatchModel::class));
    }

    public function test_dispatch_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(DispatchLineModel::class));
    }

    // ── WIMS — Inventory ──────────────────────────────────────────────────────

    public function test_inventory_level_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryLevelModel::class));
    }

    public function test_inventory_valuation_layer_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryValuationLayerModel::class));
    }

    public function test_inventory_batch_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryBatchModel::class));
    }

    public function test_inventory_serial_number_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventorySerialNumberModel::class));
    }

    public function test_inventory_cycle_count_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryCycleCountModel::class));
    }

    public function test_inventory_cycle_count_line_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryCycleCountLineModel::class));
    }

    public function test_inventory_location_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventoryLocationModel::class));
    }

    public function test_inventory_setting_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(InventorySettingModel::class));
    }

    // ── WIMS — GS1 traceability ───────────────────────────────────────────────

    public function test_gs1_identifier_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(Gs1IdentifierModel::class));
    }

    public function test_gs1_barcode_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(Gs1BarcodeModel::class));
    }

    // ── WIMS — Warehouse zone ─────────────────────────────────────────────────

    public function test_warehouse_zone_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(WarehouseZoneModel::class));
    }

    // ── UoM module ────────────────────────────────────────────────────────────

    public function test_uom_category_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(UomCategoryModel::class));
    }

    public function test_unit_of_measure_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(UnitOfMeasureModel::class));
    }

    public function test_uom_conversion_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(UomConversionModel::class));
    }

    public function test_product_uom_setting_model_uses_has_audit_trait(): void
    {
        $this->assertArrayHasKey(HasAudit::class, class_uses_recursive(ProductUomSettingModel::class));
    }

    // ── HasAudit filterAuditableAttributes ────────────────────────────────────

    public function test_filter_auditable_attributes_excludes_password(): void
    {
        // Use a mock that has the trait mixed in
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use HasAudit;
        };

        $attrs = ['name' => 'Alice', 'password' => 'secret', 'email' => 'alice@example.com'];
        $filtered = $model->filterAuditableAttributes($attrs);

        $this->assertArrayHasKey('name', $filtered);
        $this->assertArrayHasKey('email', $filtered);
        $this->assertArrayNotHasKey('password', $filtered);
    }

    public function test_filter_auditable_attributes_respects_audit_include(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use HasAudit;
            protected array $auditInclude = ['name'];
        };

        $attrs = ['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active'];
        $filtered = $model->filterAuditableAttributes($attrs);

        $this->assertSame(['name' => 'Alice'], $filtered);
    }

    public function test_filter_auditable_attributes_respects_audit_exclude(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use HasAudit;
            protected array $auditExclude = ['secret_token'];
        };

        $attrs = ['name' => 'Alice', 'secret_token' => 'abc123'];
        $filtered = $model->filterAuditableAttributes($attrs);

        $this->assertArrayHasKey('name', $filtered);
        $this->assertArrayNotHasKey('secret_token', $filtered);
    }

    // ── AuditAction::register is idempotent ───────────────────────────────────

    public function test_audit_action_register_is_idempotent(): void
    {
        $before = count(AuditAction::all());
        AuditAction::register('created'); // built-in, should not duplicate
        $this->assertSame($before, count(AuditAction::all()));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeAuditLog(
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return new AuditLog(
            id: 1,
            tenantId: 1,
            userId: 1,
            event: AuditAction::from(AuditAction::CREATED),
            auditableType: 'AccountModel',
            auditableId: 42,
            oldValues: $oldValues,
            newValues: $newValues,
            url: null,
            ipAddress: null,
            userAgent: null,
            tags: null,
            metadata: null,
            createdAt: new \DateTimeImmutable,
        );
    }
}
