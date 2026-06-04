<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InvoiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('invoice_types') || ! Schema::hasTable('tenants')) {
            return;
        }

        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            foreach ($this->types() as $type) {
                DB::table('invoice_types')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'code' => $type['code'],
                    ],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => null,
                        'name' => $type['name'],
                        'module_key' => $type['module_key'],
                        'direction' => $type['direction'],
                        'schema_version' => 1,
                        'number_sequence_key' => $type['number_sequence_key'],
                        'document_type_id' => $this->documentTypeId($tenantId, $type['document_type_codes']),
                        'default_status' => 'draft',
                        'settings_json' => $this->json([
                            'seed_source' => 'invoice_module',
                            'source_modules' => $type['source_modules'],
                            'source_types' => $type['source_types'],
                        ]),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }, 3);
    }

    private function tenantId(): ?int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  list<string>  $codes
     */
    private function documentTypeId(int $tenantId, array $codes): ?int
    {
        if (! Schema::hasTable('document_types')) {
            return null;
        }

        foreach ($codes as $code) {
            $id = DB::table('document_types')
                ->where('code', $code)
                ->where(function ($query) use ($tenantId): void {
                    $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                })
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        foreach ($codes as $code) {
            $id = DB::table('document_types')->where('code', $code)->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * @return list<array{
     *     code:string,
     *     name:string,
     *     module_key:?string,
     *     direction:string,
     *     number_sequence_key:string,
     *     document_type_codes:list<string>,
     *     source_modules:list<string>,
     *     source_types:list<string>
     * }>
     */
    private function types(): array
    {
        return [
            [
                'code' => 'PURCHASE_INVOICE',
                'name' => 'Purchase Invoice',
                'module_key' => 'purchase',
                'direction' => 'payable',
                'number_sequence_key' => 'PURCHASE_INVOICE',
                'document_type_codes' => ['PURCHASE_INVOICE', 'purchase_invoice'],
                'source_modules' => ['purchase'],
                'source_types' => ['purchase_order', 'grn_header', 'purchase_return', 'direct_purchase'],
            ],
            [
                'code' => 'SALES_INVOICE',
                'name' => 'Sales Invoice',
                'module_key' => 'sales',
                'direction' => 'receivable',
                'number_sequence_key' => 'SALES_INVOICE',
                'document_type_codes' => ['SALES_INVOICE', 'sales_invoice'],
                'source_modules' => ['sales'],
                'source_types' => ['sales_order', 'gdn_header', 'sales_return', 'direct_sales'],
            ],
            [
                'code' => 'VEHICLE_SERVICE_INVOICE',
                'name' => 'Vehicle Service Invoice',
                'module_key' => 'vehicle_service',
                'direction' => 'receivable',
                'number_sequence_key' => 'VEHICLE_SERVICE_INVOICE',
                'document_type_codes' => ['VEHICLE_SERVICE_INVOICE', 'service_invoice'],
                'source_modules' => ['vehicle_service', 'inventory'],
                'source_types' => ['job_card', 'job_card_line', 'diagnostic', 'inspection', 'external_service'],
            ],
            [
                'code' => 'VEHICLE_RENTAL_INVOICE',
                'name' => 'Vehicle Rental Invoice',
                'module_key' => 'vehicle_rental',
                'direction' => 'receivable',
                'number_sequence_key' => 'VEHICLE_RENTAL_INVOICE',
                'document_type_codes' => ['VEHICLE_RENTAL_INVOICE', 'rental_invoice'],
                'source_modules' => ['vehicle_rental'],
                'source_types' => ['rental_agreement', 'running_chart', 'replacement', 'extra_charge'],
            ],
            [
                'code' => 'VOUCHER_PAYABLE_INVOICE',
                'name' => 'Voucher Payable Invoice',
                'module_key' => 'voucher',
                'direction' => 'payable',
                'number_sequence_key' => 'VOUCHER_PAYABLE_INVOICE',
                'document_type_codes' => ['VOUCHER_PAYMENT', 'VOUCHER_EXPENSE', 'voucher'],
                'source_modules' => ['voucher'],
                'source_types' => ['voucher_payment', 'voucher_expense', 'voucher_line'],
            ],
            [
                'code' => 'VOUCHER_RECEIVABLE_INVOICE',
                'name' => 'Voucher Receivable Invoice',
                'module_key' => 'voucher',
                'direction' => 'receivable',
                'number_sequence_key' => 'VOUCHER_RECEIVABLE_INVOICE',
                'document_type_codes' => ['VOUCHER_RECEIPT', 'voucher', 'receipt'],
                'source_modules' => ['voucher'],
                'source_types' => ['voucher_receipt', 'voucher_line'],
            ],
            [
                'code' => 'INTERNAL_INVOICE',
                'name' => 'Internal Invoice',
                'module_key' => null,
                'direction' => 'internal',
                'number_sequence_key' => 'INTERNAL_INVOICE',
                'document_type_codes' => ['GENERIC'],
                'source_modules' => ['purchase', 'sales', 'vehicle_service', 'vehicle_rental', 'voucher'],
                'source_types' => ['internal_charge', 'intercompany', 'cross_module'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload): string
    {
        return (string) json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
