<?php

declare(strict_types=1);

namespace Tests\Feature\Invoice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Services\DecimalMath;
use Modules\User\Models\UserModel;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class InvoiceSettlementEligibilityFilterTest extends TestCase
{
    use RefreshDatabase;

    private const PARTY_ID = 901;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_settlement_filter_returns_only_open_invoices_for_the_requested_currency_and_scope(): void
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        $currencyId = $this->currency('LKR', 'Sri Lankan Rupee', false);
        $otherCurrencyId = $this->currency('USD', 'US Dollar');

        $includedPosted = $this->invoice(
            $tenantId,
            $organizationUnitId,
            $currencyId,
            'RENT-POSTED',
            'posted',
            'unpaid',
            '100.000000',
        );
        $includedPartial = $this->invoice(
            $tenantId,
            $organizationUnitId,
            $currencyId,
            'RENT-PARTIAL',
            'partially_paid',
            'partial',
            '40.000000',
        );
        $this->invoice(
            $tenantId,
            $organizationUnitId,
            $currencyId,
            'RENT-PAID',
            'paid',
            'paid',
            '0.000000',
        );
        $this->invoice(
            $tenantId,
            $organizationUnitId,
            $currencyId,
            'RENT-DRAFT',
            'draft',
            'unpaid',
            '100.000000',
        );
        $this->invoice(
            $tenantId,
            $organizationUnitId,
            $otherCurrencyId,
            'RENT-OTHER-CURRENCY',
            'posted',
            'unpaid',
            '100.000000',
        );
        $this->invoice(
            $tenantId,
            $organizationUnitId,
            $currencyId,
            'RENT-OTHER-PARTY',
            'posted',
            'unpaid',
            '100.000000',
            self::PARTY_ID + 1,
        );

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );
        $this->actingAs($user);

        $response = $this->tenantGetJson(
            $tenantId,
            '/api/v1/invoices?'.http_build_query([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'invoice_type' => 'rental',
                'direction' => 'outbound',
                'party_id' => self::PARTY_ID,
                'currency_id' => $currencyId,
                'settlement_eligible' => 'true',
                'per_page' => 25,
            ]),
        )->assertOk()->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->map(static fn (mixed $id): int => (int) $id)->sort()->values()->all();

        $this->assertSame([$includedPosted, $includedPartial], $ids);
    }

    /**
     * @return array{int, int, int}
     */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'INV-'.$suffix,
            'name' => 'Invoice Filter '.$suffix,
            'slug' => 'invoice-filter-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'code' => 'ORG-'.$suffix,
            'name' => 'Invoice Organization '.$suffix,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Invoice',
            'last_name' => 'Filter',
            'email' => 'invoice-filter-'.Str::lower($suffix).'@example.test',
            'status' => 'active',
        ]);

        return [$tenantId, $organizationUnitId, $userId];
    }

    private function currency(string $code, string $name, bool $isActive = true): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => $name,
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoice(
        int $tenantId,
        int $organizationUnitId,
        int $currencyId,
        string $number,
        string $status,
        string $balanceStatus,
        string $remainingAmount,
        int $partyId = self::PARTY_ID,
    ): int {
        $now = now();
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_number' => $number,
            'invoice_type' => 'rental',
            'direction' => 'outbound',
            'party_type' => 'customer',
            'party_id' => $partyId,
            'invoice_date' => '2026-07-12',
            'currency_id' => $currencyId,
            'currency_code_snapshot' => DB::table('currencies')->where('id', $currencyId)->value('code'),
            'exchange_rate' => '1.000000',
            'status' => $status,
            'subtotal' => '100.000000',
            'grand_total' => '100.000000',
            'balance_due' => $remainingAmount,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_id' => $invoiceId,
            'invoice_total' => '100.000000',
            'paid_amount' => app(DecimalMath::class)->sub('100.000000', $remainingAmount),
            'remaining_amount' => $remainingAmount,
            'status' => $balanceStatus,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $invoiceId;
    }
}
