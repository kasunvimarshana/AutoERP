<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\ChequePrintLog;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\UserModel;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class ChequeTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_create_cheque_template_via_api(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $payload = [
            'tenant_id' => $tenantId,
            'template_name' => 'Commercial Bank Standard Cheque',
            'bank_name' => 'Commercial Bank of Ceylon',
            'page_width_mm' => '210.000',
            'page_height_mm' => '99.000',
            'date_x_mm' => '155.000',
            'date_y_mm' => '12.000',
            'payee_x_mm' => '25.000',
            'payee_y_mm' => '34.000',
            'amount_x_mm' => '160.000',
            'amount_y_mm' => '45.000',
            'amount_words_x_mm' => '25.000',
            'amount_words_y_mm' => '55.000',
            'cheque_number_x_mm' => '15.000',
            'cheque_number_y_mm' => '12.000',
            'font_size' => '12.00',
            'font_family' => 'Arial',
            'is_default' => true,
            'is_active' => true,
            'metadata' => [
                'date_format' => 'd/m/Y',
                'crossing_type' => 'ac_payee',
                'amount_prefix' => '**',
                'amount_suffix' => '**',
            ],
        ];

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->postJson('api/v1/payments/cheque-templates', $payload));

        $response->assertCreated();
        $response->assertJsonPath('data.template_name', 'Commercial Bank Standard Cheque');
        $response->assertJsonPath('data.bank_name', 'Commercial Bank of Ceylon');
        $response->assertJsonPath('data.row_version', 1);
        $response->assertJsonPath('data.is_default', true);
        $response->assertJsonPath('data.can_delete', false);

        $this->assertDatabaseHas('cheque_templates', [
            'tenant_id' => $tenantId,
            'template_name' => 'Commercial Bank Standard Cheque',
            'row_version' => 1,
            'is_default' => true,
        ]);
    }

    public function test_spatial_bounds_validation_rejects_coordinates_exceeding_page(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $payload = [
            'tenant_id' => $tenantId,
            'template_name' => 'Off Page Cheque',
            'page_width_mm' => '200.000',
            'page_height_mm' => '90.000',
            'date_x_mm' => '250.000',
            'date_y_mm' => '10.000',
            'payee_x_mm' => '20.000',
            'payee_y_mm' => '120.000',
            'amount_x_mm' => '150.000',
            'amount_y_mm' => '40.000',
            'amount_words_x_mm' => '20.000',
            'amount_words_y_mm' => '50.000',
            'font_size' => '12.00',
        ];

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->postJson('api/v1/payments/cheque-templates', $payload));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['date_x_mm', 'payee_y_mm']);
    }

    public function test_setting_new_default_template_atomically_clears_previous_default(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template1 = $this->createTemplate($tenantId, 'Template Alpha', isDefault: true);
        self::assertTrue((bool) $template1->is_default);

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->postJson('api/v1/payments/cheque-templates', [
            'tenant_id' => $tenantId,
            'template_name' => 'Template Beta',
            'page_width_mm' => '210.000',
            'page_height_mm' => '99.000',
            'date_x_mm' => '155.000',
            'date_y_mm' => '12.000',
            'payee_x_mm' => '25.000',
            'payee_y_mm' => '34.000',
            'amount_x_mm' => '160.000',
            'amount_y_mm' => '45.000',
            'amount_words_x_mm' => '25.000',
            'amount_words_y_mm' => '55.000',
            'font_size' => '12.00',
            'is_default' => true,
            'is_active' => true,
        ]));

        $response->assertCreated();
        $response->assertJsonPath('data.is_default', true);

        $template1->refresh();
        self::assertFalse((bool) $template1->is_default);
    }

    public function test_updating_cheque_template_with_correct_version_increments_row_version(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Original Name');
        self::assertSame(1, (int) $template->row_version);

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("api/v1/payments/cheque-templates/{$template->id}", [
            'tenant_id' => $tenantId,
            'expected_version' => 1,
            'template_name' => 'Updated Name',
            'page_width_mm' => '210.000',
            'page_height_mm' => '99.000',
            'date_x_mm' => '150.000',
            'date_y_mm' => '15.000',
            'payee_x_mm' => '30.000',
            'payee_y_mm' => '35.000',
            'amount_x_mm' => '165.000',
            'amount_y_mm' => '45.000',
            'amount_words_x_mm' => '30.000',
            'amount_words_y_mm' => '55.000',
            'font_size' => '14.00',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.template_name', 'Updated Name');
        $response->assertJsonPath('data.row_version', 2);

        $template->refresh();
        self::assertSame(2, (int) $template->row_version);
        self::assertSame('Updated Name', $template->template_name);
    }

    public function test_updating_cheque_template_with_stale_version_triggers_conflict_http_exception(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Stale Test Template');

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("api/v1/payments/cheque-templates/{$template->id}", [
            'tenant_id' => $tenantId,
            'expected_version' => 999,
            'template_name' => 'Concurrent Conflict Name',
            'page_width_mm' => '210.000',
            'page_height_mm' => '99.000',
            'date_x_mm' => '150.000',
            'date_y_mm' => '15.000',
            'payee_x_mm' => '30.000',
            'payee_y_mm' => '35.000',
            'amount_x_mm' => '165.000',
            'amount_y_mm' => '45.000',
            'amount_words_x_mm' => '30.000',
            'amount_words_y_mm' => '55.000',
            'font_size' => '14.00',
        ]));

        $response->assertStatus(409);
    }

    public function test_default_template_cannot_be_deleted(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Default Locked Template', isDefault: true);

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->deleteJson("api/v1/payments/cheque-templates/{$template->id}", [
            'tenant_id' => $tenantId,
        ]));

        $response->assertStatus(500);
        $this->assertDatabaseHas('cheque_templates', ['id' => $template->id, 'deleted_at' => null]);
    }

    public function test_template_with_print_history_cannot_be_deleted(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Printed Cheque Template', isDefault: false);
        [$payment, $line] = $this->paymentFixture($tenantId);

        $this->withTenantExecutionContext($tenantId, fn () => ChequePrintLog::query()->create([
            'tenant_id' => $tenantId,
            'payment_id' => $payment->id,
            'payment_line_id' => $line->id,
            'cheque_template_id' => $template->id,
            'printed_at' => now(),
            'print_status' => 'printed',
            'notes' => 'Audited print entry',
        ]));

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->deleteJson("api/v1/payments/cheque-templates/{$template->id}", [
            'tenant_id' => $tenantId,
        ]));

        $response->assertStatus(500);
        $this->assertDatabaseHas('cheque_templates', ['id' => $template->id, 'deleted_at' => null]);
    }

    public function test_unreferenced_non_default_template_can_be_deleted(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Disposable Template', isDefault: false);

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->deleteJson("api/v1/payments/cheque-templates/{$template->id}", [
            'tenant_id' => $tenantId,
        ]));

        $response->assertNoContent();
        $this->assertSoftDeleted('cheque_templates', ['id' => $template->id]);
    }

    public function test_index_supports_pagination_search_and_all_filter(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $this->createTemplate($tenantId, 'Sampath Bank A4', isDefault: false, bankName: 'Sampath Bank');
        $this->createTemplate($tenantId, 'Hatton National Bank Leaf', isDefault: false, bankName: 'Hatton National Bank');

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->getJson("api/v1/payments/cheque-templates?tenant_id={$tenantId}&search=Sampath"));
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.template_name', 'Sampath Bank A4');

        $allResponse = $this->withTenantExecutionContext($tenantId, fn () => $this->getJson("api/v1/payments/cheque-templates?tenant_id={$tenantId}&all=1"));
        $allResponse->assertOk();
        $allResponse->assertJsonCount(2, 'data');
    }

    public function test_cheque_print_export_html_renders_accurate_cheque_layout(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Export Layout Template', isDefault: true);
        [$payment, $line] = $this->paymentFixture($tenantId);

        $response = $this->withTenantExecutionContext($tenantId, fn () => $this->get("api/v1/payments/{$payment->id}/lines/{$line->id}/cheque-print/export/html?tenant_id={$tenantId}"));

        $response->assertOk();
        $response->assertSee('Acme Supplier Ltd.');
        $response->assertSee('**12,500.00**');
        $response->assertSee('Twelve Thousand Five Hundred Only');
        $response->assertSee('A/C PAYEE ONLY');
    }

    public function test_payment_with_printed_cheques_cannot_be_deleted(): void
    {
        $tenantId = $this->tenantId();
        $this->actingAsSuperAdmin($tenantId);

        $template = $this->createTemplate($tenantId, 'Audit Template', isDefault: false);
        [$payment, $line] = $this->paymentFixture($tenantId);

        $this->withTenantExecutionContext($tenantId, fn () => ChequePrintLog::query()->create([
            'tenant_id' => $tenantId,
            'payment_id' => $payment->id,
            'payment_line_id' => $line->id,
            'cheque_template_id' => $template->id,
            'printed_at' => now(),
            'print_status' => 'printed',
        ]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Payments with printed cheques cannot be deleted.');

        $this->withTenantExecutionContext($tenantId, fn () => $payment->delete());
    }

    private function actingAsSuperAdmin(int $tenantId): UserModel
    {
        $now = now();
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Payment',
            'last_name' => 'Admin',
            'email' => 'payment-admin-'.Str::lower(Str::random(8)).'@example.test',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserSystemRole::SUPER_ADMIN_NAME,
            'guard_name' => UserGuard::TENANT_API,
            'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = $this->withTenantExecutionContext($tenantId, fn () => UserModel::query()->findOrFail($userId));
        $this->actingAs($user);

        return $user;
    }

    private function createTemplate(int $tenantId, string $name, bool $isDefault = false, string $bankName = 'Sampath Bank'): ChequeTemplate
    {
        return $this->withTenantExecutionContext($tenantId, fn () => ChequeTemplate::query()->create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'bank_name' => $bankName,
            'template_name' => $name,
            'page_width_mm' => '210.000',
            'page_height_mm' => '99.000',
            'date_x_mm' => '155.000',
            'date_y_mm' => '12.000',
            'payee_x_mm' => '25.000',
            'payee_y_mm' => '34.000',
            'amount_x_mm' => '160.000',
            'amount_y_mm' => '45.000',
            'amount_words_x_mm' => '25.000',
            'amount_words_y_mm' => '55.000',
            'cheque_number_x_mm' => '15.000',
            'cheque_number_y_mm' => '12.000',
            'font_size' => '12.00',
            'font_family' => 'Arial',
            'is_default' => $isDefault,
            'is_active' => true,
            'metadata' => [
                'date_format' => 'd/m/Y',
                'crossing_type' => 'ac_payee',
                'amount_prefix' => '**',
                'amount_suffix' => '**',
            ],
        ]));
    }

    /**
     * @return array{0: Payment, 1: PaymentLine}
     */
    private function paymentFixture(int $tenantId): array
    {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId): array {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CHQ-'.Str::upper(Str::random(6)),
                'name' => 'Cheque Method',
                'method_type' => PaymentMethodType::Cheque->value,
                'direction_allowed' => 'outbound',
                'requires_reference' => true,
                'requires_instrument_details' => true,
                'is_active' => true,
            ]);

            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'payment_number' => 'PAY-CHQ-'.Str::upper(Str::random(4)),
                'payment_type' => 'supplier_payment',
                'direction' => 'outbound',
                'document_status' => PaymentDocumentStatus::Approved->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::Posted->value,
                'instrument_status' => PaymentInstrumentStatus::Pending->value,
                'payment_date' => '2026-06-11',
                'reference_number' => 'CHQ-8800',
                'cheque_number' => '8800',
                'cheque_date' => '2026-06-11',
                'payee_name' => 'Acme Supplier Ltd.',
                'total_amount' => '12500.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '12500.000000',
                'refunded_amount' => '0.000000',
            ]);
            $payment->save();

            $line = new PaymentLine;
            $line->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => PaymentMethodType::Cheque->value,
                'requires_reference_snapshot' => true,
                'requires_instrument_details_snapshot' => true,
                'reference_number' => 'CHQ-8800',
                'instrument_direction' => 'issued',
                'instrument_number' => '8800',
                'instrument_date' => '2026-06-11',
                'amount' => '12500.000000',
                'cleared_amount' => '0.000000',
                'status' => 'pending',
            ]);
            $line->save();

            return [$payment, $line];
        });
    }

    private function tenantId(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CHQ-'.$suffix,
            'name' => 'Cheque Tenant '.$suffix,
            'slug' => 'cheque-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
