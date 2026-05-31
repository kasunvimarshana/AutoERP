<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PaymentModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (! Schema::hasTable('payments') || ! Schema::hasTable('payment_methods')) {
                return;
            }

            $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
            if ($tenantId < 1) {
                $tenantId = (int) DB::table('tenants')->value('id');
            }

            if ($tenantId < 1) {
                return;
            }

            $organizationUnitId = (int) DB::table('organization_units')
                ->where('tenant_id', $tenantId)
                ->where('code', 'MAIN')
                ->value('id');
            $organizationUnitId = $organizationUnitId > 0 ? $organizationUnitId : null;

            $cashAccountId = $this->accountId($tenantId, '1000');
            $bankAccountId = $this->accountId($tenantId, '1010') ?: $cashAccountId;
            $currencyId = Schema::hasTable('currencies')
                ? (int) DB::table('currencies')->where('code', 'LKR')->value('id')
                : 0;
            $currencyId = $currencyId > 0 ? $currencyId : null;

            $this->seedPaymentMethods($tenantId, $organizationUnitId, $cashAccountId, $bankAccountId);
            $this->seedCashRegister($tenantId, $organizationUnitId, $cashAccountId);
            $this->seedSamplePayment($tenantId, $organizationUnitId, $cashAccountId, $currencyId);
        });
    }

    private function seedPaymentMethods(int $tenantId, ?int $organizationUnitId, ?int $cashAccountId, ?int $bankAccountId): void
    {
        $methods = [
            ['CASH', 'Cash', 'cash', $cashAccountId],
            ['BANK_TRANSFER', 'Bank Transfer', 'bank_transfer', $bankAccountId],
            ['CARD', 'Card', 'card', $bankAccountId],
            ['CHECK', 'Check', 'check', $bankAccountId],
            ['ONLINE_TRANSFER', 'Online Transfer', 'online', $bankAccountId],
        ];

        foreach ($methods as [$code, $name, $type, $accountId]) {
            DB::table('payment_methods')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'account_id' => $accountId,
                    'is_active' => true,
                    'metadata' => json_encode(['seed_source' => 'payment_module']),
                    'name' => $name,
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedCashRegister(int $tenantId, ?int $organizationUnitId, ?int $cashAccountId): void
    {
        if (! Schema::hasTable('cash_registers') || $cashAccountId === null) {
            return;
        }

        DB::table('cash_registers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'code' => 'MAIN-CASH'],
            [
                'cash_account_id' => $cashAccountId,
                'current_balance' => 0,
                'is_active' => true,
                'metadata' => json_encode(['seed_source' => 'payment_module']),
                'name' => 'Main Cash Register',
                'opening_balance' => 0,
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'status' => 'closed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedSamplePayment(int $tenantId, ?int $organizationUnitId, ?int $cashAccountId, ?int $currencyId): void
    {
        if ($cashAccountId === null) {
            return;
        }

        $methodId = (int) DB::table('payment_methods')
            ->where('tenant_id', $tenantId)
            ->where('code', 'CASH')
            ->value('id');

        if ($methodId < 1) {
            return;
        }

        DB::table('payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'payment_number' => 'PAY-SAMPLE-00001'],
            [
                'account_id' => $cashAccountId,
                'allocated_amount' => 0,
                'amount' => 1000,
                'base_amount' => 1000,
                'currency_id' => $currencyId,
                'direction' => 'inbound',
                'exchange_rate' => 1,
                'metadata' => json_encode([
                    'currency_code' => 'LKR',
                    'direction' => 'generic_receipt',
                    'party_name' => 'Walk-in payer',
                    'payment_method_name' => 'Cash',
                    'seed_source' => 'payment_module',
                ]),
                'organization_unit_id' => $organizationUnitId,
                'party_role' => 'payer',
                'party_type' => 'external_party',
                'payer_name' => 'Walk-in payer',
                'payment_date' => now()->toDateString(),
                'payment_method_id' => $methodId,
                'reference' => 'PAY-SEED',
                'row_version' => 1,
                'source_module' => 'shared',
                'source_reference' => 'SEED-REFERENCE',
                'source_type' => 'generic_reference',
                'status' => 'draft',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function accountId(int $tenantId, string $code): ?int
    {
        if (! Schema::hasTable('accounts')) {
            return null;
        }

        $id = (int) DB::table('accounts')->where('tenant_id', $tenantId)->where('code', $code)->value('id');

        return $id > 0 ? $id : null;
    }
}
