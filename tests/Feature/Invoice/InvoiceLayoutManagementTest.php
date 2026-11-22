<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Invoice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Invoice\Constants\InvoicePermission;
use Modules\Invoice\Contracts\InvoiceLayoutResolverInterface;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLayoutTemplate;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceLayoutProvisioner;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class InvoiceLayoutManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_provisions_system_default_layouts_on_initial_list(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        $response = $this->withAuth($context)->getJson('/api/v1/invoice-layouts');
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'code', 'name', 'paper_size', 'orientation', 'is_system', 'is_active', 'row_version']]]);

        $data = $response->json('data');
        $codes = collect($data)->pluck('code')->all();

        $this->assertContains(InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4, $codes);
        $this->assertContains(InvoiceLayoutProvisioner::SYSTEM_MODERN_A4, $codes);
        $this->assertContains(InvoiceLayoutProvisioner::SYSTEM_COMPACT_A5, $codes);
        $this->assertContains(InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL, $codes);
        $this->assertContains(InvoiceLayoutProvisioner::SYSTEM_CONTINUOUS, $codes);

        // Verify all 5 are marked as system layouts
        $systemLayouts = collect($data)->where('is_system', true);
        $this->assertCount(5, $systemLayouts);
    }

    public function test_create_read_and_filter_custom_invoice_layout(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        // 1. Create a custom layout
        $createPayload = [
            'code' => 'custom_retail_v1',
            'name' => 'Custom Retail Receipt',
            'description' => 'Tailored receipt template for weekend retail sales.',
            'paper_size' => InvoicePaperSize::A5->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Compact->value,
            'is_active' => true,
            'custom_css' => '.retail-brand { color: #2563eb; font-weight: bold; }',
            'header_content' => 'Weekend Flash Sale - No Exchanges on Clearance',
            'footer_content' => 'Customer service hotline: +1 800 555 RETAIL',
            'settings' => [
                'primary_color' => '#2563eb',
                'font_family' => 'sans-serif',
                'show_company_logo' => true,
                'show_tax_summary' => true,
                'show_payment_details' => true,
                'show_signatures' => false,
                'show_amount_in_words' => false,
                'show_notes' => true,
            ],
        ];

        $createResponse = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', $createPayload);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.code', 'custom_retail_v1');
        $createResponse->assertJsonPath('data.name', 'Custom Retail Receipt');
        $createResponse->assertJsonPath('data.is_system', false);
        $createResponse->assertJsonPath('data.row_version', 1);
        $createResponse->assertJsonPath('data.settings.primary_color', '#2563eb');

        $layoutId = (int) $createResponse->json('data.id');

        // 2. Read single layout
        $showResponse = $this->withAuth($context)->getJson("/api/v1/invoice-layouts/{$layoutId}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.id', $layoutId);
        $showResponse->assertJsonPath('data.code', 'custom_retail_v1');

        // 3. Reject duplicate code
        $dupResponse = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', $createPayload);
        $dupResponse->assertStatus(422);
        $dupResponse->assertJsonValidationErrors(['code']);

        // 4. Reject script tags in CSS or header content
        $xssPayload = array_merge($createPayload, [
            'code' => 'malicious_layout',
            'custom_css' => '<script>alert("hack")</script>',
        ]);
        $xssResponse = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', $xssPayload);
        $xssResponse->assertStatus(422);

        // 5. Filter list by search term
        $filterResponse = $this->withAuth($context)->getJson('/api/v1/invoice-layouts?search=retail');
        $filterResponse->assertOk();
        $this->assertCount(1, $filterResponse->json('data'));
        $this->assertSame('custom_retail_v1', $filterResponse->json('data.0.code'));
    }

    public function test_update_custom_layout_with_optimistic_locking_and_conflict_rejection(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        // Create custom layout
        $created = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', [
            'code' => 'counter_fast_v1',
            'name' => 'Counter Fast v1',
            'paper_size' => InvoicePaperSize::A5->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Compact->value,
        ])->assertCreated()->json('data');

        $layoutId = (int) $created['id'];
        $this->assertSame(1, $created['row_version']);

        // Update with matching expected_version = 1
        $updateResponse = $this->withAuth($context)->putJson("/api/v1/invoice-layouts/{$layoutId}", [
            'expected_version' => 1,
            'name' => 'Counter Fast v1 Updated',
            'paper_size' => InvoicePaperSize::A5->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Compact->value,
            'header_content' => 'Updated counter notes',
        ]);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.name', 'Counter Fast v1 Updated');
        $updateResponse->assertJsonPath('data.row_version', 2);
        $updateResponse->assertJsonPath('data.header_content', 'Updated counter notes');

        // Stale update with obsolete expected_version = 1 fails with 409 Conflict
        $staleResponse = $this->withAuth($context)->putJson("/api/v1/invoice-layouts/{$layoutId}", [
            'expected_version' => 1,
            'name' => 'Should fail conflict',
        ]);
        $staleResponse->assertStatus(409);
    }

    public function test_cannot_delete_system_layout_or_layout_referenced_by_invoice(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
            InvoicePermission::CREATE,
        ]);

        // 1. Initial list provisions system layouts
        $layouts = $this->withAuth($context)->getJson('/api/v1/invoice-layouts')->assertOk()->json('data');
        $systemLayout = collect($layouts)->firstWhere('code', InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4);
        $this->assertNotNull($systemLayout);
        $systemLayoutId = (int) $systemLayout['id'];

        // 2. Attempt to delete system layout fails with 422
        $deleteSystemResponse = $this->withAuth($context)->deleteJson("/api/v1/invoice-layouts/{$systemLayoutId}");
        $deleteSystemResponse->assertStatus(422);
        $deleteSystemResponse->assertJsonValidationErrors(['layout']);

        // 3. Create a custom layout
        $custom = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', [
            'code' => 'service_assigned_v1',
            'name' => 'Service Assigned Template',
            'paper_size' => InvoicePaperSize::A4->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Standard->value,
        ])->assertCreated()->json('data');

        $customId = (int) $custom['id'];

        // 4. Create an invoice referencing this layout
        $customerId = $this->customer($context['tenant_id'], $context['organization_unit_id']);
        $invoice = $this->createInvoiceWithLayout($context['tenant_id'], $context['organization_unit_id'], $customerId, $customId);

        // 5. Attempt to delete custom layout referenced by invoice fails with 422
        $deleteInUseResponse = $this->withAuth($context)->deleteJson("/api/v1/invoice-layouts/{$customId}");
        $deleteInUseResponse->assertStatus(422);
        $deleteInUseResponse->assertJsonValidationErrors(['layout']);

        // 6. Create an unused custom layout and delete it successfully
        $unused = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', [
            'code' => 'temporary_unused_layout',
            'name' => 'Temporary Unused Template',
            'paper_size' => InvoicePaperSize::A5->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Compact->value,
        ])->assertCreated()->json('data');

        $unusedId = (int) $unused['id'];
        $deleteSuccess = $this->withAuth($context)->deleteJson("/api/v1/invoice-layouts/{$unusedId}");
        $deleteSuccess->assertOk();

        // Layout is now soft deleted
        $this->assertSoftDeleted('invoice_layouts', ['id' => $unusedId]);
    }

    public function test_preview_layout_renders_html_with_mock_and_actual_invoice(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        // List to provision
        $layouts = $this->withAuth($context)->getJson('/api/v1/invoice-layouts')->assertOk()->json('data');
        $modernLayout = collect($layouts)->firstWhere('code', InvoiceLayoutProvisioner::SYSTEM_MODERN_A4);
        $this->assertNotNull($modernLayout);
        $modernId = (int) $modernLayout['id'];

        // 1. Preview with sample mock invoice
        $previewSample = $this->withAuth($context)->postJson("/api/v1/invoice-layouts/{$modernId}/preview");
        $previewSample->assertOk();
        $previewSample->assertJsonStructure(['data' => ['html']]);
        $html = $previewSample->json('data.html');
        $this->assertStringContainsString('modern-invoice', $html);
        $this->assertStringContainsString('Full Vehicle Diagnostic', $html);
        $this->assertStringContainsString('Grand Total', $html);

        // 2. Preview thermal layout
        $thermalLayout = collect($layouts)->firstWhere('code', InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL);
        $this->assertNotNull($thermalLayout);
        $thermalId = (int) $thermalLayout['id'];

        $previewThermal = $this->withAuth($context)->postJson("/api/v1/invoice-layouts/{$thermalId}/preview");
        $previewThermal->assertOk();
        $thermalHtml = $previewThermal->json('data.html');
        $this->assertStringContainsString('thermal-receipt', $thermalHtml);
        $this->assertStringContainsString('TOTAL:', $thermalHtml);
    }

    public function test_set_default_layout_and_hierarchical_resolution(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        $tenantId = $context['tenant_id'];
        $ouId = $context['organization_unit_id'];

        // Second organization unit for scope test
        $otherOuId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'code' => 'OU-SECONDARY',
            'name' => 'Secondary Branch',
        ]);

        // 1. Set default layout at Tenant scope to modern_a4
        $setDefaultTenant = $this->withAuth($context)->postJson('/api/v1/invoice-layouts/set-default', [
            'code' => InvoiceLayoutProvisioner::SYSTEM_MODERN_A4,
            'organization_unit_id' => null,
        ]);
        $setDefaultTenant->assertOk();
        $setDefaultTenant->assertJsonPath('data.code', InvoiceLayoutProvisioner::SYSTEM_MODERN_A4);
        $setDefaultTenant->assertJsonPath('data.scope', 'tenant');

        /** @var InvoiceLayoutResolverInterface $resolver */
        $resolver = app(InvoiceLayoutResolverInterface::class);

        // Resolver for other OU (which has no specific override) resolves to tenant default: modern_a4
        $resolvedOther = $resolver->resolveForScope($tenantId, $otherOuId);
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_MODERN_A4, $resolvedOther->code);

        // 2. Set default layout for child branch OU to compact_a5
        $setDefaultOu = $this->withAuth($context)->postJson('/api/v1/invoice-layouts/set-default', [
            'code' => InvoiceLayoutProvisioner::SYSTEM_COMPACT_A5,
            'organization_unit_id' => $otherOuId,
        ]);
        $setDefaultOu->assertOk();
        $setDefaultOu->assertJsonPath('data.code', InvoiceLayoutProvisioner::SYSTEM_COMPACT_A5);
        $setDefaultOu->assertJsonPath('data.scope', 'organization_unit');

        // Child OU resolves to compact_a5
        $resolvedOtherBranch = $resolver->resolveForScope($tenantId, $otherOuId);
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_COMPACT_A5, $resolvedOtherBranch->code);

        // Primary root OU still resolves to tenant default modern_a4
        $resolvedPrimaryAgain = $resolver->resolveForScope($tenantId, $ouId);
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_MODERN_A4, $resolvedPrimaryAgain->code);
    }

    public function test_tenant_isolation_boundary_enforcement(): void
    {
        $contextA = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);
        $contextB = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
        ]);

        // Tenant A creates custom layout
        $layoutA = $this->withAuth($contextA)->postJson('/api/v1/invoice-layouts', [
            'code' => 'tenant_a_exclusive',
            'name' => 'Tenant A Exclusive Layout',
            'paper_size' => InvoicePaperSize::A4->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'view_template' => InvoiceLayoutTemplate::Standard->value,
        ])->assertCreated()->json('data');

        $layoutAId = (int) $layoutA['id'];

        // Tenant B cannot view Tenant A's layout
        $this->withAuth($contextB)->getJson("/api/v1/invoice-layouts/{$layoutAId}")
            ->assertNotFound();

        // Tenant B cannot update Tenant A's layout
        $this->withAuth($contextB)->putJson("/api/v1/invoice-layouts/{$layoutAId}", [
            'expected_version' => 1,
            'name' => 'Hacked Name',
        ])->assertNotFound();

        // Tenant B cannot delete Tenant A's layout
        $this->withAuth($contextB)->deleteJson("/api/v1/invoice-layouts/{$layoutAId}")
            ->assertNotFound();
    }

    public function test_printer_scope_layout_assignment_and_resolution(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
            InvoicePermission::VIEW,
        ]);
        $tenantId = (int) $context['tenant_id'];
        $ouId = (int) $context['organization_unit_id'];

        // 1. Assign printer pos_front_desk to thermal layout
        $assignPos = $this->withAuth($context)->postJson('/api/v1/invoice-layouts/set-default', [
            'code' => InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL,
            'scope' => 'printer',
            'printer' => 'pos_front_desk',
        ]);
        $assignPos->assertOk();
        $assignPos->assertJsonPath('data.scope', 'printer');
        $assignPos->assertJsonPath('data.printer', 'pos_front_desk');
        $assignPos->assertJsonPath('data.code', InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL);

        // 2. Assign printer dotmatrix_wh to continuous layout
        $assignContinuous = $this->withAuth($context)->postJson('/api/v1/invoice-layouts/set-default', [
            'code' => InvoiceLayoutProvisioner::SYSTEM_CONTINUOUS,
            'scope' => 'printer',
            'printer' => 'dotmatrix_wh',
        ]);
        $assignContinuous->assertOk();

        // 3. Query printer assignments list endpoint
        $listRes = $this->withAuth($context)->getJson('/api/v1/invoice-layouts/printer-assignments');
        $listRes->assertOk();
        $listRes->assertJsonPath('data.assignments.pos_front_desk', InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL);
        $listRes->assertJsonPath('data.assignments.dotmatrix_wh', InvoiceLayoutProvisioner::SYSTEM_CONTINUOUS);

        // 4. Test resolution via InvoiceLayoutResolverInterface
        /** @var InvoiceLayoutResolverInterface $resolver */
        $resolver = app(InvoiceLayoutResolverInterface::class);

        $resolvedPos = $resolver->resolveForScope($tenantId, $ouId, 'pos_front_desk');
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL, $resolvedPos->code);

        $resolvedDm = $resolver->resolveForScope($tenantId, $ouId, 'dotmatrix_wh');
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_CONTINUOUS, $resolvedDm->code);

        // Unassigned printer falls back to standard_a4
        $resolvedDefault = $resolver->resolveForScope($tenantId, $ouId, 'unassigned_printer');
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4, $resolvedDefault->code);

        // 5. Create invoice and verify invoice resolution with printer parameter
        $customerId = $this->customer($tenantId, $ouId);
        $invoice = $this->createInvoiceWithLayout($tenantId, $ouId, $customerId, null);

        $resolvedInvoicePos = $resolver->resolve($invoice, printer: 'pos_front_desk');
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_POS_THERMAL, $resolvedInvoicePos->code);

        // 6. Delete printer assignment
        $delRes = $this->withAuth($context)->deleteJson('/api/v1/invoice-layouts/printer-assignments/pos_front_desk');
        $delRes->assertOk();
        $delRes->assertJsonPath('data.printer', 'pos_front_desk');

        // After deletion, pos_front_desk falls back to default layout
        $resolvedAfterDel = $resolver->resolveForScope($tenantId, $ouId, 'pos_front_desk');
        $this->assertSame(InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4, $resolvedAfterDel->code);
    }

    private function createInvoiceWithLayout(int $tenantId, int $organizationUnitId, int $customerId, ?int $layoutId = null): Invoice
    {
        return app(TenantExecutionContextInterface::class)->runForTenant($tenantId, function () use ($tenantId, $organizationUnitId, $customerId, $layoutId): Invoice {
            $invoice = new Invoice;
            $invoice->tenant_id = $tenantId;
            $invoice->organization_unit_id = $organizationUnitId;
            $invoice->invoice_number = 'INV-LAYOUT-'.Str::upper(Str::random(6));
            $invoice->invoice_type = InvoiceType::Manual;
            $invoice->direction = InvoiceDirection::Outbound;
            $invoice->party_type = InvoicePartyType::Customer->value;
            $invoice->party_id = $customerId;
            $invoice->party_name_snapshot = 'Layout Test Customer';
            $invoice->invoice_date = now()->format('Y-m-d');
            $invoice->exchange_rate = '1.000000';
            $invoice->subtotal = '100.000000';
            $invoice->grand_total = '100.000000';
            $invoice->balance_due = '100.000000';
            $invoice->invoice_layout_id = $layoutId;
            $invoice->row_version = 1;
            $invoice->save();

            return $invoice;
        });
    }

    public function test_index_includes_active_defaults_and_layout_assignment_to_invoice(): void
    {
        $context = $this->createAuthContext([
            InvoicePermission::LAYOUTS_VIEW,
            InvoicePermission::LAYOUTS_MANAGE,
            InvoicePermission::VIEW,
        ]);

        // 1. List layouts and verify meta defaults
        $response = $this->withAuth($context)->getJson('/api/v1/invoice-layouts')->assertOk();
        $this->assertSame('standard_a4', $response->json('meta.defaults.tenant_default'));
        $data = $response->json('data');
        $standardLayout = collect($data)->firstWhere('code', 'standard_a4');
        $this->assertNotNull($standardLayout);
        $this->assertTrue($standardLayout['is_tenant_default']);

        // 2. Create custom layout
        $custom = $this->withAuth($context)->postJson('/api/v1/invoice-layouts', [
            'code' => 'assigned_layout_v1',
            'name' => 'Assigned Special Layout',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'view_template' => 'invoice.layouts.modern',
            'is_active' => true,
        ])->assertCreated()->json('data');

        // 3. Create an invoice
        $customerId = $this->customer($context['tenant_id'], $context['organization_unit_id']);
        $invoice = $this->createInvoiceWithLayout($context['tenant_id'], $context['organization_unit_id'], $customerId);

        $resolver = app(InvoiceLayoutResolverInterface::class);
        $resolvedDefault = $resolver->resolve($invoice);
        $this->assertSame('standard_a4', $resolvedDefault->code);

        // 4. Assign custom layout to invoice
        $patchResponse = $this->withAuth($context)->patchJson("/api/v1/invoices/{$invoice->id}/layout", [
            'invoice_layout_id' => $custom['id'],
        ])->assertOk();

        $this->assertSame($custom['id'], $patchResponse->json('data.invoice_layout_id'));
        $this->assertSame('assigned_layout_v1', $patchResponse->json('data.layout.code'));

        // 5. Verify resolver now automatically picks the assigned layout
        $invoice->refresh();
        $this->assertSame($custom['id'], (int) $invoice->invoice_layout_id);
        $resolvedAssigned = $resolver->resolve($invoice);
        $this->assertSame('assigned_layout_v1', $resolvedAssigned->code);

        // 6. Reset assigned layout back to null (inheriting default)
        $resetResponse = $this->withAuth($context)->patchJson("/api/v1/invoices/{$invoice->id}/layout", [
            'invoice_layout_id' => null,
        ])->assertOk();

        $this->assertNull($resetResponse->json('data.invoice_layout_id'));
        $invoice->refresh();
        $this->assertNull($invoice->invoice_layout_id);
        $resolvedReset = $resolver->resolve($invoice);
        $this->assertSame('standard_a4', $resolvedReset->code);
    }

    private function customer(int $tenantId, int $organizationUnitId): int
    {
        $now = now();

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUST-'.Str::upper(Str::random(6)),
            'code' => 'CUST-'.Str::upper(Str::random(6)),
            'customer_type' => 'individual',
            'display_name' => 'Customer '.Str::random(6),
            'name' => 'Customer '.Str::random(6),
            'legal_name' => 'Customer Legal '.Str::random(6),
            'tax_registration_number' => 'TAX-'.Str::upper(Str::random(6)),
            'phone' => '011'.random_int(1000000, 9999999),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array{tenant_id: int, organization_unit_id: int, user_id: int, role_id: int, token: string} */
    private function createAuthContext(array $permissions, ?int $tenantId = null): array
    {
        $now = now();
        $code = 'TNT-'.Str::upper(Str::random(6));
        $tenantId = $tenantId ?? (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant '.Str::random(8),
            'slug' => 'tenant-'.Str::lower(Str::random(8)),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Integration test tenant.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main Facility',
            'code' => 'MAIN-'.Str::upper(Str::random(4)),
        ]);

        $email = 'user-'.Str::lower(Str::random(8)).'@example.test';
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Layout',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => 'secret-password',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Layout Role '.Str::random(6),
            'guard_name' => 'auth-api',
            'description' => 'Layout role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($permissions as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('tenant_id', $tenantId)
                ->where('name', $permissionName)
                ->where('guard_name', 'auth-api')
                ->value('id');

            if ($permissionId === null) {
                $permissionId = (int) DB::table('permissions')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $permissionName,
                    'guard_name' => 'auth-api',
                    'module' => 'invoice',
                    'description' => $permissionName,
                    'is_active' => true,
                    'row_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $token = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $organizationUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'token' => $token,
        ];
    }

    private function withAuth(array $context): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$context['token'],
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }
}
