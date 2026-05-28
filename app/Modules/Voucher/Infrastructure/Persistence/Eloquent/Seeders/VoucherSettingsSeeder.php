<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\Support\VoucherDocumentSeedCatalog;

class VoucherSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VoucherDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');
        $definitionIdsByTypeCode = [];

        foreach ($documentTypeIds as $code => $documentTypeId) {
            $definitionIdsByTypeCode[(string) $code] = (int) DB::table('document_definitions')
                ->where('tenant_id', $tenantId)
                ->where('document_type_id', (int) $documentTypeId)
                ->where('version', 1)
                ->value('id');
        }

        DB::table('voucher_settings')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
            ],
            [
                'default_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_JOURNAL'] ?? null,
                'payment_voucher_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_PAYMENT'] ?? null,
                'receipt_voucher_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_RECEIPT'] ?? null,
                'journal_voucher_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_JOURNAL'] ?? null,
                'contra_voucher_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_CONTRA'] ?? null,
                'expense_voucher_document_definition_id' => $definitionIdsByTypeCode['VOUCHER_EXPENSE'] ?? null,
                'require_approval' => true,
                'allow_direct_posting' => false,
                'allow_reversal' => true,
                'allow_partial_allocation' => true,
                'default_sequence_period_type' => 'yearly',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $typeRows = [];
        foreach (VoucherDocumentSeedCatalog::voucherTypes() as $voucherType) {
            $documentTypeId = $documentTypeIds[$voucherType['document_type_code']] ?? null;
            if ($documentTypeId === null) {
                continue;
            }

            $typeRows[] = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'name' => $voucherType['name'],
                'code' => $voucherType['code'],
                'direction' => $voucherType['direction'],
                'posting_behavior' => 'manual',
                'document_type_id' => $documentTypeId,
                'document_definition_id' => $definitionIdsByTypeCode[$voucherType['document_type_code']] ?? null,
                'requires_approval' => true,
                'allow_direct_posting' => false,
                'allow_reversal' => true,
                'allow_partial_allocation' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($typeRows !== []) {
            DB::table('voucher_types')->upsert(
                $typeRows,
                ['tenant_id', 'organization_unit_id', 'code'],
                [
                    'name',
                    'direction',
                    'posting_behavior',
                    'document_type_id',
                    'document_definition_id',
                    'requires_approval',
                    'allow_direct_posting',
                    'allow_reversal',
                    'allow_partial_allocation',
                    'is_active',
                    'updated_at',
                ],
            );
        }
    }
}
