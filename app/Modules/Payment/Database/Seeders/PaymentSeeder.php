<?php

declare(strict_types=1);

namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedDefaultMethods();
        }, 3);
    }

    private function seedDefaultMethods(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $methods = [
            ['CASH', 'Cash', 'cash', 'both', false, false, 10],
            ['CHEQUE', 'Cheque', 'cheque', 'both', true, true, 20],
            ['BANK_TRANSFER', 'Bank Transfer', 'bank_transfer', 'both', true, true, 30],
            ['CARD', 'Card', 'card', 'both', true, true, 40],
            ['MOBILE_WALLET', 'Mobile Wallet', 'mobile_wallet', 'both', true, false, 50],
            ['DIRECT_DEBIT', 'Direct Debit', 'direct_debit', 'both', true, true, 60],
            ['OTHER', 'Other', 'other', 'both', false, false, 999],
        ];

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach ($methods as [$code, $name, $type, $direction, $requiresReference, $requiresBank, $sortOrder]) {
                DB::table('payment_methods')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'organization_unit_id' => null, 'code' => $code],
                    [
                        'scope_key' => 'tenant:'.$tenantId,
                        'name' => $name,
                        'method_type' => $type,
                        'direction_allowed' => $direction,
                        'requires_reference' => $requiresReference,
                        'requires_bank_account' => $requiresBank,
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                        'metadata' => json_encode(['seed_source' => 'payment_module'], JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }
}

