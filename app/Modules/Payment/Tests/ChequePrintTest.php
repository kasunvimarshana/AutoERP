<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\ChequePrintLog;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentAuthorizationService;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserModel;
use Tests\TestCase;

final class ChequePrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_cheque_template_crud(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $payload = $this->templatePayload($tenantId);

        $created = $this->postJson('/api/v1/payments/cheque-templates', $payload)
            ->assertCreated()
            ->assertJsonPath('data.template_name', 'Default Bank Cheque')
            ->assertJsonPath('data.is_active', true)
            ->json('data');

        $this->getJson('/api/v1/payments/cheque-templates?tenant_id='.$tenantId)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/payments/cheque-templates/'.$created['id'].'?tenant_id='.$tenantId)
            ->assertOk()
            ->assertJsonPath('data.bank_name', 'Example Bank');

        $this->putJson('/api/v1/payments/cheque-templates/'.$created['id'], [
            'tenant_id' => $tenantId,
            'template_name' => 'Updated Bank Cheque',
            'date_x_mm' => '160.500',
        ])->assertOk()
            ->assertJsonPath('data.template_name', 'Updated Bank Cheque')
            ->assertJsonPath('data.date_x_mm', '160.500');

        $this->postJson('/api/v1/payments/cheque-templates', [
            ...$payload,
            'template_name' => 'Replacement Default Cheque',
        ])->assertCreated();

        $this->deleteJson('/api/v1/payments/cheque-templates/'.$created['id'], [
            'tenant_id' => $tenantId,
        ])->assertNoContent();

        $this->assertSoftDeleted('cheque_templates', ['id' => $created['id']]);
    }

    public function test_preview_returns_payment_and_template_data_for_valid_cheque_payment(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $template = $this->createTemplate($tenantId);
        $payment = $this->createPayment($tenantId, PaymentMethodType::Cheque, PaymentStatus::Approved);

        $this->getJson($this->previewUrl($payment, $template, $tenantId))
            ->assertOk()
            ->assertJsonPath('data.payment.payment_number', 'PAY-0001')
            ->assertJsonPath('data.line.payment_method', 'cheque')
            ->assertJsonPath('data.line.payee_name', 'ABC Supplier')
            ->assertJsonPath('data.line.amount', '12500.000000')
            ->assertJsonPath('data.line.amount_in_words', 'Twelve Thousand Five Hundred Only')
            ->assertJsonPath('data.line.cheque_date', '2026-06-11')
            ->assertJsonPath('data.line.formatted_cheque_date', '11/06/2026')
            ->assertJsonPath('data.template.template_name', 'Default Bank Cheque');
    }

    public function test_preview_rejects_non_cheque_payment(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $template = $this->createTemplate($tenantId);
        $payment = $this->createPayment($tenantId, PaymentMethodType::Cash, PaymentStatus::Approved);

        $this->getJson($this->previewUrl($payment, $template, $tenantId))
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Selected payment line is not cheque-capable.');
    }

    public function test_preview_rejects_cancelled_and_reversed_payments(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $template = $this->createTemplate($tenantId);

        foreach ([PaymentStatus::Cancelled, PaymentStatus::Reversed] as $status) {
            $payment = $this->createPayment(
                $tenantId,
                PaymentMethodType::Cheque,
                $status,
                'PAY-'.$status->value,
            );

            $this->getJson($this->previewUrl($payment, $template, $tenantId))
                ->assertUnprocessable()
                ->assertJsonPath('error.message', 'Only approved or posted cheque payments can be printed.');
        }
    }

    public function test_mark_printed_creates_print_log(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $template = $this->createTemplate($tenantId);
        $payment = $this->createPayment($tenantId, PaymentMethodType::Cheque, PaymentStatus::Posted);

        $lineId = $this->lineId($payment);
        $this->postJson('/api/v1/payments/'.$payment->getKey().'/lines/'.$lineId.'/cheque-print', [
            'tenant_id' => $tenantId,
            'cheque_template_id' => $template->getKey(),
            'notes' => 'Test print confirmed',
        ])->assertCreated()
            ->assertJsonPath('data.payment_id', $payment->getKey())
            ->assertJsonPath('data.cheque_template_id', $template->getKey())
            ->assertJsonPath('data.print_status', 'printed');

        $this->assertDatabaseHas('cheque_print_logs', [
            'tenant_id' => $tenantId,
            'payment_id' => $payment->getKey(),
            'payment_line_id' => $lineId,
            'cheque_template_id' => $template->getKey(),
            'print_status' => 'printed',
            'notes' => 'Test print confirmed',
        ]);
        $this->assertSame(1, ChequePrintLog::query()->count());
    }

    public function test_tenant_and_organization_isolation_are_enforced(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant();
        $orgOne = $this->createOrganizationUnit($tenantId, 'ORG-A');
        $orgTwo = $this->createOrganizationUnit($tenantId, 'ORG-B');
        $template = $this->createTemplate($tenantId, $orgOne);
        $payment = $this->createPayment(
            $tenantId,
            PaymentMethodType::Cheque,
            PaymentStatus::Approved,
            'PAY-SCOPE',
            $orgOne,
        );
        $lineId = $this->lineId($payment);

        $this->authorize($otherTenantId);
        $this->getJson('/api/v1/payments/'.$payment->getKey().'/lines/'.$lineId.'/cheque-print/preview?tenant_id='.$otherTenantId.'&cheque_template_id='.$template->getKey())
            ->assertNotFound();

        $this->authorize($tenantId, $orgOne);
        $this->getJson('/api/v1/payments/'.$payment->getKey().'/lines/'.$lineId.'/cheque-print/preview?tenant_id='.$tenantId.'&organization_unit_id='.$orgTwo.'&cheque_template_id='.$template->getKey())
            ->assertNotFound();

        $otherOrgTemplate = $this->createTemplate($tenantId, $orgTwo, 'Other Org Cheque');
        $this->getJson('/api/v1/payments/'.$payment->getKey().'/lines/'.$lineId.'/cheque-print/preview?tenant_id='.$tenantId.'&organization_unit_id='.$orgOne.'&cheque_template_id='.$otherOrgTemplate->getKey())
            ->assertNotFound();
    }

    public function test_inactive_template_is_rejected(): void
    {
        $tenantId = $this->createTenant();
        $this->authorize($tenantId);
        $template = $this->createTemplate($tenantId);
        $template->forceFill(['is_active' => false])->save();
        $payment = $this->createPayment($tenantId, PaymentMethodType::Cheque, PaymentStatus::Approved);

        $this->getJson($this->previewUrl($payment, $template, $tenantId))
            ->assertNotFound();
    }

    private function previewUrl(Payment $payment, ChequeTemplate $template, int $tenantId): string
    {
        return '/api/v1/payments/'.$payment->getKey().'/lines/'.$this->lineId($payment).'/cheque-print/preview'
            .'?tenant_id='.$tenantId
            .'&cheque_template_id='.$template->getKey();
    }

    private function lineId(Payment $payment): int
    {
        return (int) PaymentLine::query()->where('payment_id', $payment->getKey())->valueOrFail('id');
    }

    private function createPayment(
        int $tenantId,
        PaymentMethodType $methodType,
        PaymentStatus $status,
        string $number = 'PAY-0001',
        ?int $organizationUnitId = null,
    ): Payment {
        $method = PaymentMethod::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => Str::upper($methodType->value).'-'.Str::upper(Str::random(6)),
            'name' => ucfirst($methodType->value),
            'method_type' => $methodType->value,
            'direction_allowed' => 'outbound',
            'requires_reference' => $methodType === PaymentMethodType::Cheque,
            'requires_bank_account' => $methodType === PaymentMethodType::Cheque,
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_number' => $number,
            'payment_type' => 'supplier_payment',
            'direction' => 'outbound',
            'payment_date' => '2026-06-11',
            'reference_number' => $methodType === PaymentMethodType::Cheque ? 'CHQ-1001' : null,
            'cheque_number' => $methodType === PaymentMethodType::Cheque ? '1001' : null,
            'cheque_date' => $methodType === PaymentMethodType::Cheque ? '2026-06-11' : null,
            'payee_name' => 'ABC Supplier',
            'status' => $status->value,
            'total_amount' => '12500.000000',
            'allocated_amount' => '0.000000',
            'unapplied_amount' => '12500.000000',
            'refunded_amount' => '0.000000',
        ]);

        PaymentLine::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_id' => $payment->getKey(),
            'payment_method_id' => $method->getKey(),
            'reference_number' => $payment->reference_number,
            'amount' => '12500.000000',
            'cleared_amount' => '0.000000',
            'status' => 'pending',
        ]);

        return $payment;
    }

    private function createTemplate(
        int $tenantId,
        ?int $organizationUnitId = null,
        string $name = 'Default Bank Cheque',
    ): ChequeTemplate {
        return ChequeTemplate::query()->create([
            ...$this->templatePayload($tenantId, $organizationUnitId),
            'template_name' => $name,
        ]);
    }

    private function templatePayload(int $tenantId, ?int $organizationUnitId = null): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'bank_name' => 'Example Bank',
            'template_name' => 'Default Bank Cheque',
            'page_width_mm' => '210',
            'page_height_mm' => '99',
            'date_x_mm' => '155',
            'date_y_mm' => '12',
            'payee_x_mm' => '25',
            'payee_y_mm' => '34',
            'amount_x_mm' => '160',
            'amount_y_mm' => '45',
            'amount_words_x_mm' => '25',
            'amount_words_y_mm' => '55',
            'cheque_number_x_mm' => '15',
            'cheque_number_y_mm' => '12',
            'font_size' => '12',
            'font_family' => 'Arial',
            'is_default' => true,
            'is_active' => true,
            'metadata' => ['date_format' => 'd/m/Y'],
        ];
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CHQ-'.$suffix,
            'name' => 'Cheque Tenant '.$suffix,
            'slug' => 'cheque-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
    {
        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorize(int $tenantId, ?int $organizationUnitId = null): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $now = now();
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => 'Cheque',
            'last_name' => 'Administrator',
            'email' => 'cheque-admin-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserPermission::SUPER_ADMIN_ROLE,
            'guard_name' => $guard,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (PaymentAuthorizationService::descriptions() as $name => $description) {
            DB::table('permissions')->insert([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => $guard,
                'module' => 'Payment',
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(UserModel::query()->findOrFail($userId));
    }
}
