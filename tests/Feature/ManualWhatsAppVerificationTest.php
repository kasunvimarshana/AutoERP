<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerWhatsAppVerificationService;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierWhatsAppVerificationService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class ManualWhatsAppVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_primary_contact_can_be_manually_verified_and_number_change_invalidates_status(): void
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-WA-1',
            'code' => 'CUS-WA-1',
            'name' => 'WhatsApp Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'mobile' => '0711111111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contactId = (int) DB::table('customer_contacts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'contact_name' => 'Primary Customer Contact',
            'mobile' => '0772626124',
            'is_primary' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withTenantExecutionContext($tenantId, function () use ($customerId, $contactId, $tenantId, $userId): void {
            $customer = Customer::query()->findOrFail($customerId);
            $service = app(CustomerWhatsAppVerificationService::class);
            $challenge = $service->start($customer, $userId, (string) Str::uuid());
            $code = $this->codeFromUrl($challenge['whatsapp_url']);

            self::assertSame('pending', $challenge['status']);
            self::assertSame('94772626124', $challenge['recipient']['phone']);
            self::assertSame('customer_primary_contact_mobile', $challenge['recipient']['source']);
            self::assertNotSame($code, DB::table('customer_whatsapp_verifications')->value('code_hash'));

            $verified = $service->confirm($customer, $userId, $code);
            self::assertSame('verified', $verified['status']);
            self::assertSame('Manual Verifier', $verified['verified_by']);

            DB::table('customer_contacts')->where('id', $contactId)->update([
                'mobile' => '0762222222',
                'updated_at' => now(),
            ]);

            self::assertSame('number_changed', $service->state($customer->refresh())['status']);
            $listCustomers = Customer::query()->whereKey($customerId)->get();
            $service->attachListSummaries($listCustomers);
            self::assertSame('number_changed', $listCustomers->first()->getAttribute('whatsapp_contact')['status']);
            self::assertSame('94762222222', $listCustomers->first()->getAttribute('whatsapp_contact')['phone']);

            self::assertDatabaseHas('customer_whatsapp_verifications', [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'normalized_phone' => '94772626124',
                'status' => 'verified',
                'verified_by' => $userId,
            ]);
        });
    }

    public function test_supplier_master_mobile_fallback_tracks_failed_attempt_and_accepts_the_reply_code(): void
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        $supplierId = (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => 'SUP-WA-1',
            'code' => 'SUP-WA-1',
            'name' => 'WhatsApp Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'mobile' => '0712345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withTenantExecutionContext($tenantId, function () use ($supplierId, $tenantId, $userId): void {
            $supplier = Supplier::query()->findOrFail($supplierId);
            $service = app(SupplierWhatsAppVerificationService::class);
            $challenge = $service->start($supplier, $userId, (string) Str::uuid());
            $code = $this->codeFromUrl($challenge['whatsapp_url']);

            self::assertSame('supplier_mobile', $challenge['recipient']['source']);

            try {
                $service->confirm($supplier, $userId, 'WA-00000000');
                self::fail('Expected an incorrect verification code to be rejected.');
            } catch (ValidationException $exception) {
                self::assertStringContainsString('4 attempt(s) remain', $exception->errors()['code'][0]);
            }

            self::assertDatabaseHas('supplier_whatsapp_verifications', [
                'tenant_id' => $tenantId,
                'supplier_id' => $supplierId,
                'attempt_count' => 1,
                'status' => 'pending',
            ]);
            self::assertSame('verified', $service->confirm($supplier, $userId, $code)['status']);
            $listSuppliers = Supplier::query()->whereKey($supplierId)->get();
            $service->attachListSummaries($listSuppliers);
            self::assertSame('verified', $listSuppliers->first()->getAttribute('whatsapp_contact')['status']);
            self::assertSame('94712345678', $listSuppliers->first()->getAttribute('whatsapp_contact')['phone']);
        });
    }

    /** @return array{int, int, int} */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-WA-'.$suffix,
            'name' => 'WhatsApp Verification Tenant',
            'slug' => 'whatsapp-verification-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'WhatsApp Verification Unit',
            'code' => 'WA-'.$suffix,
        ]);
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => 'Manual',
            'last_name' => 'Verifier',
            'email' => 'verifier-'.Str::lower($suffix).'@example.test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId, $userId];
    }

    private function codeFromUrl(string $url): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertArrayHasKey('text', $query);
        self::assertMatchesRegularExpression('/WA-[A-F0-9]{8}/', (string) $query['text']);
        preg_match('/WA-[A-F0-9]{8}/', (string) $query['text'], $matches);

        return $matches[0];
    }
}
